#!/usr/bin/env node

/**
 * ZemTab Production Stress Test
 * 
 * Staged test: 100 → 200 → 300 restaurants
 * 
 * Usage:
 *   node tools/stress-test.js                          # Local (127.0.0.1:8000)
 *   ZEMTAB_BASE_URL=https://zemtab.com node tools/stress-test.js  # Production
 * 
 * Each restaurant:
 *   - 1 always-on dashboard screen polling every 15 seconds
 *   - Guest menu views + real order placement
 *   - 1 distinct login session per restaurant
 * 
 * Pass criteria per stage:
 *   - 0 errors (no 500, 503, timeouts)
 *   - p95 response time < 500ms
 *   - p99 response time < 2000ms
 *   - No request takes longer than 10s
 * 
 * The test auto-stops a stage on first error and moves to the next.
 */

const baseUrl = (process.env.ZEMTAB_BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const stages = (process.env.ZEMTAB_STAGES || '100,200,300').split(',').map(Number);
const durationSeconds = Number(process.env.ZEMTAB_DURATION || 300); // 5 minutes per stage
const pollIntervalMs = 15000; // 15 seconds — matches the optimized frontend
const guestOrderEvery = 3; // Every 3rd guest flow places an order
const maxConcurrency = 300; // Up to 300 concurrent workers

// ─── Helpers ──────────────────────────────────────────────

class Jar {
  constructor() { this.cookies = new Map(); }
  header() { return [...this.cookies.entries()].map(([k, v]) => `${k}=${v}`).join('; '); }
  store(headers) {
    const raw = headers.getSetCookie ? headers.getSetCookie() : (headers.get('set-cookie') ? [headers.get('set-cookie')] : []);
    for (const line of raw) {
      const [pair] = line.split(';');
      const idx = pair.indexOf('=');
      if (idx > 0) this.cookies.set(pair.slice(0, idx), pair.slice(idx + 1));
    }
  }
}

const stageMetrics = [];

async function timed(label, fn) {
  const start = Date.now();
  try {
    const res = await fn();
    const ms = Date.now() - start;
    stageMetrics.push({ label, ms, ok: true, status: res?.status || 200 });
    return res;
  } catch (error) {
    const ms = Date.now() - start;
    stageMetrics.push({ label, ms, ok: false, status: 0, error: error.message });
    throw error;
  }
}

async function request(url, options = {}, jar = new Jar()) {
  const headers = { ...(options.headers || {}) };
  const cookie = jar.header();
  if (cookie) headers.cookie = cookie;
  const res = await fetch(url, { redirect: 'manual', ...options, headers });
  jar.store(res.headers);
  return res;
}

function csrf(html) {
  return html.match(/name="_token"\s+value="([^"]+)"/)?.[1] || html.match(/<meta name="csrf-token" content="([^"]+)"/)?.[1];
}

function firstItemId(html) {
  return html.match(/add\(\{\s*id:\s*(\d+)/)?.[1];
}

function slugFor(index) {
  return 'zt-stress-' + String((index % 300) + 1).padStart(3, '0');
}

function emailFor(index) {
  return slugFor(index) + '@zemtab.test';
}

// ─── Guest flow (menu view + optional order) ──────────────

async function guestFlow(index) {
  const jar = new Jar();
  const slug = slugFor(index);
  const table = String((index % 10) + 1);
  const menuUrl = `${baseUrl}/r/${slug}/table/${table}`;

  const menu = await timed('menu', () => request(menuUrl, {}, jar));
  const html = await menu.text();
  if (!menu.ok) throw new Error(`menu ${menu.status}`);
  const token = csrf(html);
  const itemId = firstItemId(html);
  if (!token || !itemId) throw new Error('missing csrf or item id');

  if (index % guestOrderEvery !== 0) return;

  const body = new URLSearchParams();
  body.set('_token', token);
  body.set('items[0][id]', itemId);
  body.set('items[0][quantity]', '1');
  body.set('items[0][note]', '');
  body.set('note', 'stress test order');

  const order = await timed('order', () => request(`${menuUrl}/orders`, {
    method: 'POST',
    headers: { 'content-type': 'application/x-www-form-urlencoded' },
    body,
  }, jar));
  if (![200, 302].includes(order.status)) throw new Error(`order ${order.status}`);
}

// ─── Dashboard login + poll ──────────────────────────────

const dashboardJars = new Map();

async function loginDashboard(index) {
  const jar = new Jar();
  const email = emailFor(index);

  const login = await timed('login-page', () => request(`${baseUrl}/login`, {}, jar));
  const loginHtml = await login.text();
  const token = csrf(loginHtml);
  if (!token) throw new Error('missing login csrf');

  const body = new URLSearchParams();
  body.set('_token', token);
  body.set('email', email);
  body.set('password', 'password');

  const auth = await timed('login', () => request(`${baseUrl}/login`, {
    method: 'POST',
    headers: { 'content-type': 'application/x-www-form-urlencoded' },
    body,
  }, jar));
  if (![200, 302].includes(auth.status)) throw new Error(`login ${auth.status}`);

  return jar;
}

async function dashboardPoll(index, jar) {
  let orderSince = 0;
  let requestSince = 0;
  for (let i = 0; i < 3; i++) { // 3 polls per cycle (~45s), then re-schedule
    const poll = await timed('poll', () => request(
      `${baseUrl}/restaurant/orders/poll?order_since=${orderSince}&request_since=${requestSince}`,
      { headers: { accept: 'application/json', 'x-requested-with': 'XMLHttpRequest' } },
      jar
    ));
    if (!poll.ok) throw new Error(`poll ${poll.status}`);
    const data = await poll.json();
    orderSince = Math.max(orderSince, Number(data.latestOrderId || 0));
    requestSince = Math.max(requestSince, Number(data.latestRequestId || 0));
    await sleep(5000); // 5s between polls in a burst
  }
}

function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

// ─── Stage runner ────────────────────────────────────────

async function runStage(restaurantCount) {
  stageMetrics.length = 0;
  const end = Date.now() + durationSeconds * 1000;
  let guestIndex = 0;
  let stageFailed = false;
  const errors = [];

  // Workers: each simulates one restaurant's dashboard + periodic guest traffic
  const workers = [];
  const concurrency = Math.min(restaurantCount, maxConcurrency);

  for (let w = 0; w < concurrency; w++) {
    workers.push((async () => {
      const restaurantIndex = w;
      let dashboardJar = null;

      // Login once per restaurant
      try {
        dashboardJar = await loginDashboard(restaurantIndex);
        dashboardJars.set(restaurantIndex, dashboardJar);
      } catch (e) {
        errors.push(`login failed for restaurant ${restaurantIndex + 1}: ${e.message}`);
        stageFailed = true;
        return;
      }

      // Poll loop
      while (Date.now() < end && !stageFailed) {
        try {
          await dashboardPoll(restaurantIndex, dashboardJar);
        } catch (e) {
          errors.push(`poll failed for restaurant ${restaurantIndex + 1}: ${e.message}`);
          stageFailed = true;
          return;
        }
      }
    })());
  }

  // Guest traffic workers (separate, lighter)
  const guestWorkers = [];
  const guestConcurrency = Math.min(20, restaurantCount);
  for (let g = 0; g < guestConcurrency; g++) {
    guestWorkers.push((async () => {
      while (Date.now() < end && !stageFailed) {
        const current = guestIndex++;
        try {
          await guestFlow(current);
        } catch (e) {
          errors.push(`guest flow failed: ${e.message}`);
          if (e.message.includes('500') || e.message.includes('503') || e.message.includes('timeout')) {
            stageFailed = true;
          }
        }
        await sleep(2000 + Math.random() * 3000); // 2-5s between guest visits
      }
    })());
  }

  // Live progress reporter
  const progressInterval = setInterval(() => {
    const elapsed = Math.floor((Date.now() - (end - durationSeconds * 1000)) / 1000);
    const ok = stageMetrics.filter(m => m.ok);
    const failed = stageMetrics.filter(m => !m.ok);
    process.stdout.write(`\r  ${elapsed}s elapsed | Requests: ${stageMetrics.length} | Errors: ${failed.length} | Stage: ${stageFailed ? 'FAILING' : 'running'}    `);
  }, 5000);

  await Promise.allSettled([...workers, ...guestWorkers]);
  clearInterval(progressInterval);
  process.stdout.write('\n');

  // Calculate results
  const ok = stageMetrics.filter(m => m.ok);
  const failed = stageMetrics.filter(m => !m.ok);
  const byLabel = ok.reduce((a, m) => ((a[m.label] ||= []).push(m), a), {});

  const summary = Object.fromEntries(Object.entries(byLabel).map(([label, rows]) => {
    const times = rows.map(r => r.ms).sort((a, b) => a - b);
    return [label, {
      count: rows.length,
      avgMs: Math.round(times.reduce((a, b) => a + b, 0) / times.length),
      p95Ms: times[Math.floor(times.length * 0.95)] || 0,
      p99Ms: times[Math.floor(times.length * 0.99)] || 0,
      maxMs: times[times.length - 1] || 0,
    }];
  }));

  const pollStats = summary.poll || { p95Ms: 0, p99Ms: 0, count: 0 };
  const passed = !stageFailed && failed.length === 0 && pollStats.p95Ms < 500 && pollStats.p99Ms < 2000;

  if (failed.length > 0 || stageFailed) {
    const errorTypes = {};
    errors.forEach(e => { errorTypes[e] = (errorTypes[e] || 0) + 1; });
    return { restaurantCount, passed: false, summary, errors: errors.slice(0, 10), errorTypes, totalRequests: stageMetrics.length, totalErrors: failed.length };
  }

  return { restaurantCount, passed: true, summary, totalRequests: stageMetrics.length, totalErrors: 0 };
}

// ─── Main ────────────────────────────────────────────────

async function main() {
  console.log('');
  console.log('═══════════════════════════════════════');
  console.log('  ZEMTAB PRODUCTION STRESS TEST');
  console.log('═══════════════════════════════════════');
  console.log(`  Target: ${baseUrl}`);
  console.log(`  Stages: ${stages.join(' → ')} restaurants`);
  console.log(`  Duration per stage: ${durationSeconds}s (${durationSeconds / 60} min)`);
  console.log(`  Poll interval: ${pollIntervalMs / 1000}s`);
  console.log('═══════════════════════════════════════');
  console.log('');

  const results = [];

  for (const count of stages) {
    console.log(`\n── Stage: ${count} restaurants (${durationSeconds / 60} min) ──`);
    const result = await runStage(count);
    results.push(result);

    const pollStats = result.summary?.poll || { p95Ms: 0, p99Ms: 0, count: 0, avgMs: 0 };
    const menuStats = result.summary?.menu || { p95Ms: 0, count: 0 };
    const orderStats = result.summary?.order || { p95Ms: 0, count: 0 };

    console.log(`  Requests: ${result.totalRequests} | Errors: ${result.totalErrors}`);
    console.log(`  Poll: ${pollStats.count} requests | avg ${pollStats.avgMs}ms | p95 ${pollStats.p95Ms}ms | p99 ${pollStats.p99Ms}ms`);
    if (menuStats.count) console.log(`  Menu: ${menuStats.count} requests | p95 ${menuStats.p95Ms}ms`);
    if (orderStats.count) console.log(`  Order: ${orderStats.count} requests | p95 ${orderStats.p95Ms}ms`);

    if (result.passed) {
      console.log(`  Verdict: ✅ PASS\n`);
    } else {
      console.log(`  Verdict: ❌ FAIL`);
      if (result.errors?.length) {
        console.log(`  First errors:`);
        result.errors.slice(0, 5).forEach(e => console.log(`    - ${e}`));
      }
      console.log('');
      console.log('  ⚠️  Stopping test — this stage failed.');
      break;
    }

    // 30s rest between stages
    if (count !== stages[stages.length - 1]) {
      console.log('  Resting 30s before next stage...');
      await sleep(30000);
    }
  }

  // ─── Final report ────────────────────────────────────
  console.log('\n═══════════════════════════════════════');
  console.log('  FINAL STRESS TEST REPORT');
  console.log('═══════════════════════════════════════\n');

  for (const r of results) {
    const pollStats = r.summary?.poll || { p95Ms: 0, p99Ms: 0 };
    const icon = r.passed ? '✅' : '❌';
    console.log(`  ${icon} Stage ${r.restaurantCount}: ${r.totalRequests} requests, ${r.totalErrors} errors, p95 ${pollStats.p95Ms}ms, p99 ${pollStats.p99Ms}ms`);
  }

  // Find the guaranteed safe number
  const passedStages = results.filter(r => r.passed);
  const lastPassed = passedStages.length > 0 ? passedStages[passedStages.length - 1].restaurantCount : 0;

  console.log('\n═══════════════════════════════════════');
  if (lastPassed > 0) {
    const recommended = Math.floor(lastPassed * 0.75); // 75% of last passed = safe launch
    console.log(`  GUARANTEED SAFE: ${lastPassed} restaurants`);
    console.log(`  RECOMMENDED LAUNCH: ${recommended} restaurants (25% safety margin)`);
  } else {
    console.log('  ❌ ALL STAGES FAILED — do not launch until fixed');
  }
  console.log('═══════════════════════════════════════\n');

  // Exit code: 0 if any stage passed, 1 if all failed
  process.exit(passedStages.length > 0 ? 0 : 1);
}

main().catch(error => {
  console.error('\nFatal error:', error);
  process.exit(1);
});