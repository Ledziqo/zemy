#!/usr/bin/env node

/**
 * ZemTab 10-minute throttle-free capacity signal test.
 *
 * Required seeded accounts:
 *   zt-stress-001@zemtab.test ... zt-stress-200@zemtab.test / password
 *
 * Usage:
 *   $env:ZEMTAB_BASE_URL="https://www.zemtab.com"; node tools/capacity-10min.js
 */

const baseUrl = (process.env.ZEMTAB_BASE_URL || 'https://www.zemtab.com').replace(/\/$/, '');
const stages = (process.env.ZEMTAB_STAGES || '50,70,100,150,200').split(',').map(Number);
const stageSeconds = Number(process.env.ZEMTAB_STAGE_SECONDS || 120);
const staffScreensPerVenue = Number(process.env.ZEMTAB_STAFF_SCREENS || 2);
const pollIntervalMs = Number(process.env.ZEMTAB_POLL_INTERVAL_MS || 15000);
const loginConcurrency = Number(process.env.ZEMTAB_LOGIN_CONCURRENCY || 12);
const timeoutMs = Number(process.env.ZEMTAB_TIMEOUT_MS || 15000);

class Jar {
  constructor() { this.cookies = new Map(); }
  header() { return [...this.cookies.entries()].map(([key, value]) => `${key}=${value}`).join('; '); }
  store(headers) {
    const raw = headers.getSetCookie ? headers.getSetCookie() : (headers.get('set-cookie') ? [headers.get('set-cookie')] : []);
    for (const line of raw) {
      const [pair] = line.split(';');
      const index = pair.indexOf('=');
      if (index > 0) this.cookies.set(pair.slice(0, index), pair.slice(index + 1));
    }
  }
}

const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
const csrf = html => html.match(/name="_token"\s+value="([^"]+)"/)?.[1] || html.match(/<meta name="csrf-token" content="([^"]+)"/)?.[1];
const firstItemId = html => html.match(/add\(\{\s*id:\s*(\d+)/)?.[1] || html.match(/name="items\[0\]\[id\]"\s+value="(\d+)"/)?.[1];
const firstProfileId = html => html.match(/name="profile_id"[^>]*value="(\d+)"/)?.[1] || html.match(/value="(\d+)"[^>]*name="profile_id"/)?.[1];
const slugFor = index => 'zt-stress-' + String(index + 1).padStart(3, '0');
const emailFor = index => `${slugFor(index)}@zemtab.test`;
const tableFor = index => String((index % 10) + 1);

async function mapLimit(items, limit, worker) {
  const results = [];
  let next = 0;
  const runners = Array.from({ length: Math.min(limit, items.length) }, async () => {
    while (next < items.length) {
      const index = next++;
      results[index] = await worker(items[index], index);
    }
  });
  await Promise.all(runners);
  return results;
}

async function request(url, options = {}, jar = new Jar(), metrics, label = 'request') {
  const headers = { ...(options.headers || {}) };
  const cookie = jar.header();
  if (cookie) headers.cookie = cookie;

  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), timeoutMs);
  const started = Date.now();

  try {
    const response = await fetch(url, {
      redirect: options.followRedirects === false ? 'manual' : 'follow',
      ...options,
      headers,
      signal: controller.signal,
    });
    jar.store(response.headers);
    const ms = Date.now() - started;
    metrics.push({ label, ms, status: response.status, ok: response.ok || [302, 303].includes(response.status) });
    return response;
  } catch (error) {
    const ms = Date.now() - started;
    metrics.push({ label, ms, status: 0, ok: false, error: error.name === 'AbortError' ? 'timeout' : error.message });
    throw error;
  } finally {
    clearTimeout(timeout);
  }
}

async function loginStaffSession(venueIndex, metrics) {
  const jar = new Jar();
  const loginPage = await request(`${baseUrl}/login`, {}, jar, metrics, 'login-page');
  const loginHtml = await loginPage.text();
  if (!loginPage.ok) throw new Error(`login page ${loginPage.status}`);
  const loginToken = csrf(loginHtml);
  if (!loginToken) throw new Error('missing login csrf');

  const loginBody = new URLSearchParams();
  loginBody.set('_token', loginToken);
  loginBody.set('email', emailFor(venueIndex));
  loginBody.set('password', 'password');

  const login = await request(`${baseUrl}/login`, {
    method: 'POST',
    headers: { 'content-type': 'application/x-www-form-urlencoded' },
    body: loginBody,
    followRedirects: false,
  }, jar, metrics, 'login');
  await login.text();
  if (![200, 302, 303].includes(login.status)) throw new Error(`login ${login.status}`);

  const profilePage = await request(`${baseUrl}/restaurant/profile-select`, {}, jar, metrics, 'profile-page');
  const profileHtml = await profilePage.text();
  if (!profilePage.ok) throw new Error(`profile page ${profilePage.status}`);

  const profileToken = csrf(profileHtml);
  const profileId = firstProfileId(profileHtml);
  if (!profileToken || !profileId) throw new Error('missing profile csrf or profile id');

  const profileBody = new URLSearchParams();
  profileBody.set('_token', profileToken);
  profileBody.set('profile_id', profileId);
  profileBody.set('password', 'password');

  const profile = await request(`${baseUrl}/restaurant/profile-login`, {
    method: 'POST',
    headers: { 'content-type': 'application/x-www-form-urlencoded' },
    body: profileBody,
    followRedirects: false,
  }, jar, metrics, 'profile-login');
  if (![200, 302, 303].includes(profile.status)) throw new Error(`profile login ${profile.status}`);

  return jar;
}

async function pollLoop(jar, stopAt, metrics, venueIndex, screenIndex, state) {
  await sleep(Math.random() * pollIntervalMs);
  let orderSince = 0;
  let requestSince = 0;

  while (Date.now() < stopAt && !state.failed) {
    try {
      const response = await request(
        `${baseUrl}/restaurant/orders/poll?order_since=${orderSince}&request_since=${requestSince}`,
        { headers: { accept: 'application/json', 'x-requested-with': 'XMLHttpRequest' }, followRedirects: false },
        jar,
        metrics,
        'poll'
      );
      if (!response.ok) throw new Error(`poll ${response.status}`);
      const data = await response.json();
      orderSince = Math.max(orderSince, Number(data.latestOrderId || 0));
      requestSince = Math.max(requestSince, Number(data.latestRequestId || 0));
    } catch (error) {
      state.errors.push(`poll v${venueIndex + 1}/s${screenIndex + 1}: ${error.message}`);
      if (/500|502|503|timeout|Abort/i.test(error.message)) state.failed = true;
    }
    await sleep(pollIntervalMs);
  }
}

async function guestLoop(venueIndex, stopAt, metrics, state) {
  await sleep(Math.random() * 5000);
  let cycle = 0;
  const slug = slugFor(venueIndex);
  const table = tableFor(venueIndex);
  const menuUrl = `${baseUrl}/r/${slug}/table/${table}`;

  while (Date.now() < stopAt && !state.failed) {
    const jar = new Jar();
    try {
      const menu = await request(menuUrl, {}, jar, metrics, 'menu');
      const html = await menu.text();
      if (!menu.ok) throw new Error(`menu ${menu.status}`);
      const token = csrf(html);
      const itemId = firstItemId(html);
      if (!token || !itemId) throw new Error('missing menu csrf or item id');

      if (cycle % 4 === 0) {
        const body = new URLSearchParams();
        body.set('_token', token);
        body.set('items[0][id]', itemId);
        body.set('items[0][quantity]', '1');
        body.set('items[0][note]', '');
        body.set('note', 'capacity test order');
        const order = await request(`${menuUrl}/orders`, {
          method: 'POST',
          headers: { 'content-type': 'application/x-www-form-urlencoded' },
          body,
          followRedirects: false,
        }, jar, metrics, 'order');
        if (![200, 302, 303].includes(order.status)) throw new Error(`order ${order.status}`);
      } else if (cycle % 4 === 1) {
        const body = new URLSearchParams();
        body.set('_token', token);
        body.set('type', 'call_waiter');
        body.set('note', 'capacity test request');
        const service = await request(`${menuUrl}/service-requests`, {
          method: 'POST',
          headers: { 'content-type': 'application/x-www-form-urlencoded' },
          body,
          followRedirects: false,
        }, jar, metrics, 'service-request');
        if (![200, 302, 303].includes(service.status)) throw new Error(`service ${service.status}`);
      }
    } catch (error) {
      state.errors.push(`guest v${venueIndex + 1}: ${error.message}`);
      if (/500|502|503|timeout|Abort/i.test(error.message)) state.failed = true;
    }
    cycle++;
    await sleep(15000 + Math.random() * 10000);
  }
}

function percentile(values, p) {
  if (values.length === 0) return 0;
  const sorted = [...values].sort((a, b) => a - b);
  return sorted[Math.min(sorted.length - 1, Math.floor(sorted.length * p))];
}

function summarize(metrics, seconds) {
  const labels = [...new Set(metrics.map(row => row.label))].sort();
  const byLabel = {};
  for (const label of labels) {
    const rows = metrics.filter(row => row.label === label);
    const times = rows.map(row => row.ms);
    byLabel[label] = {
      count: rows.length,
      p50: percentile(times, 0.50),
      p95: percentile(times, 0.95),
      p99: percentile(times, 0.99),
      max: times.length ? Math.max(...times) : 0,
      errors: rows.filter(row => !row.ok).length,
    };
  }
  return {
    totalRequests: metrics.length,
    requestsPerMinute: Math.round((metrics.length / seconds) * 60),
    errors: metrics.filter(row => !row.ok),
    byLabel,
  };
}

async function recoveryProbe(metrics) {
  const started = Date.now();
  const home = await request(`${baseUrl}/`, {}, new Jar(), metrics, 'recovery-home');
  const login = await request(`${baseUrl}/login`, {}, new Jar(), metrics, 'recovery-login');
  return { ok: home.ok && login.ok, ms: Date.now() - started };
}

async function runStage(activeVenues) {
  const metrics = [];
  const state = { failed: false, errors: [] };
  const venueIndexes = Array.from({ length: activeVenues }, (_, index) => index);

  console.log(`\nStage ${activeVenues} active venues: logging in ${activeVenues * staffScreensPerVenue} staff screens...`);
  const staffSessions = [];
  await mapLimit(venueIndexes.flatMap(venueIndex => Array.from({ length: staffScreensPerVenue }, (_, screenIndex) => ({ venueIndex, screenIndex }))), loginConcurrency, async ({ venueIndex, screenIndex }) => {
    const jar = await loginStaffSession(venueIndex, metrics);
    staffSessions.push({ venueIndex, screenIndex, jar });
  });

  console.log(`Stage ${activeVenues}: running ${stageSeconds}s...`);
  const stopAt = Date.now() + stageSeconds * 1000;
  const workers = [
    ...staffSessions.map(session => pollLoop(session.jar, stopAt, metrics, session.venueIndex, session.screenIndex, state)),
    ...venueIndexes.map(venueIndex => guestLoop(venueIndex, stopAt, metrics, state)),
  ];

  const progress = setInterval(() => {
    const summary = summarize(metrics, Math.max(1, stageSeconds - Math.ceil((stopAt - Date.now()) / 1000)));
    process.stdout.write(`\r  requests ${summary.totalRequests} | rpm ${summary.requestsPerMinute} | errors ${summary.errors.length} | ${state.failed ? 'FAILING' : 'running'}   `);
  }, 5000);

  await Promise.allSettled(workers);
  clearInterval(progress);
  process.stdout.write('\n');

  const recovery = await recoveryProbe(metrics).catch(error => ({ ok: false, ms: 0, error: error.message }));
  const summary = summarize(metrics, stageSeconds);
  const pollP95 = summary.byLabel.poll?.p95 || 0;
  const realErrors = summary.errors.filter(row => [0, 500, 502, 503].includes(row.status));
  const passed = !state.failed && realErrors.length === 0 && pollP95 < 3000 && recovery.ok;

  return { activeVenues, staffScreens: activeVenues * staffScreensPerVenue, passed, pollP95, realErrors, recovery, summary, errors: state.errors.slice(0, 10) };
}

async function main() {
  console.log(`Target: ${baseUrl}`);
  console.log(`Stages: ${stages.join(', ')} active venues`);
  console.log(`Stage duration: ${stageSeconds}s | staff screens per venue: ${staffScreensPerVenue}`);

  const results = [];
  for (const stage of stages) {
    const result = await runStage(stage);
    results.push(result);

    console.log(`  ${result.passed ? 'PASS' : 'FAIL'} ${stage} venues / ${result.staffScreens} staff screens`);
    console.log(`  requests/min: ${result.summary.requestsPerMinute} | total: ${result.summary.totalRequests} | real errors: ${result.realErrors.length} | recovery: ${result.recovery.ok ? `${result.recovery.ms}ms` : 'failed'}`);
    for (const [label, stats] of Object.entries(result.summary.byLabel)) {
      console.log(`  ${label}: count ${stats.count}, p50 ${stats.p50}ms, p95 ${stats.p95}ms, p99 ${stats.p99}ms, max ${stats.max}ms, errors ${stats.errors}`);
    }
    if (result.errors.length) {
      console.log('  sample errors:');
      result.errors.slice(0, 5).forEach(error => console.log(`    - ${error}`));
    }

    if (!result.passed) break;
    if (stage !== stages[stages.length - 1]) {
      console.log('  cooldown 15s...');
      await sleep(15000);
    }
  }

  const passed = results.filter(result => result.passed);
  const highest = passed.length ? passed[passed.length - 1].activeVenues : 0;
  console.log('\nFinal quick signal');
  console.log(`Highest passed active venues: ${highest}`);
  console.log(`Recommended launch estimate with 40% headroom: ${Math.floor(highest * 0.6)}`);
  process.exit(highest > 0 ? 0 : 1);
}

main().catch(error => {
  console.error('Fatal:', error);
  process.exit(1);
});
