#!/usr/bin/env node

const baseUrl = (process.env.ZEMTAB_BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const restaurants = Number(process.env.ZEMTAB_RESTAURANTS || 25);
const slugList = (process.env.ZEMTAB_SLUGS || '').split(',').map(s => s.trim()).filter(Boolean);
const tableList = (process.env.ZEMTAB_TABLES || '1,2,3,4,5,6,7,8,9,10').split(',').map(s => s.trim()).filter(Boolean);
const staffEmail = process.env.ZEMTAB_STAFF_EMAIL || '';
const staffPassword = process.env.ZEMTAB_STAFF_PASSWORD || 'password';
const durationSeconds = Number(process.env.ZEMTAB_DURATION || 60);
const guestLoops = Number(process.env.ZEMTAB_GUEST_LOOPS || 2);
const pollLoops = Number(process.env.ZEMTAB_POLL_LOOPS || 6);
const maxConcurrency = Number(process.env.ZEMTAB_CONCURRENCY || 40);
const orderEvery = Math.max(1, Number(process.env.ZEMTAB_ORDER_EVERY || 1));

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

const metrics = [];

async function timed(label, fn) {
  const start = Date.now();
  try {
    const res = await fn();
    const ms = Date.now() - start;
    metrics.push({ label, ms, ok: true, status: res?.status || 200 });
    return res;
  } catch (error) {
    const ms = Date.now() - start;
    metrics.push({ label, ms, ok: false, status: 0, error: error.message });
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

async function guestFlow(index) {
  const jar = new Jar();
  const table = tableList[index % tableList.length];
  const slug = slugList.length > 0
    ? slugList[index % slugList.length]
    : `zt-stress-${String((index % restaurants) + 1).padStart(3, '0')}`;
  const menuUrl = `${baseUrl}/r/${slug}/table/${table}`;

  const menu = await timed('menu', () => request(menuUrl, {}, jar));
  const html = await menu.text();
  if (!menu.ok) throw new Error(`menu ${menu.status}`);
  const token = csrf(html);
  const itemId = firstItemId(html);
  if (!token || !itemId) throw new Error('missing csrf or item id');

  if (index % orderEvery !== 0) return;

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

async function loginDashboard(index) {
  const jar = new Jar();
  const staffIndex = (index % restaurants) + 1;
  const login = await timed('login-page', () => request(`${baseUrl}/login`, {}, jar));
  const loginHtml = await login.text();
  const token = csrf(loginHtml);
  if (!token) throw new Error('missing login csrf');

  const body = new URLSearchParams();
  body.set('_token', token);
  body.set('email', staffEmail || `zt-stress-staff-${staffIndex}@zemtab.test`);
  body.set('password', staffPassword);

  const auth = await timed('login', () => request(`${baseUrl}/login`, {
    method: 'POST',
    headers: { 'content-type': 'application/x-www-form-urlencoded' },
    body,
  }, jar));
  if (![200, 302].includes(auth.status)) throw new Error(`login ${auth.status}`);

  return jar;
}

async function dashboardPoll(jar) {
  let orderSince = 0;
  let requestSince = 0;
  for (let i = 0; i < pollLoops; i++) {
    const poll = await timed('poll', () => request(`${baseUrl}/restaurant/orders/poll?order_since=${orderSince}&request_since=${requestSince}`, {
      headers: { accept: 'application/json', 'x-requested-with': 'XMLHttpRequest' },
    }, jar));
    if (!poll.ok) throw new Error(`poll ${poll.status}`);
    const data = await poll.json();
    orderSince = Math.max(orderSince, Number(data.latestOrderId || 0));
    requestSince = Math.max(requestSince, Number(data.latestRequestId || 0));
  }
}

async function main() {
  const end = Date.now() + durationSeconds * 1000;
  let index = 0;
  const workers = [];
  const concurrency = Math.min(slugList.length || restaurants, maxConcurrency);

  for (let w = 0; w < concurrency; w++) {
    workers.push((async () => {
      let dashboardJar = null;
      while (Date.now() < end) {
        const current = index++;
        await guestFlow(current);
        if (current % guestLoops === 0) {
          dashboardJar ??= await loginDashboard(current);
          await dashboardPoll(dashboardJar);
        }
      }
    })());
  }

  const results = await Promise.allSettled(workers);
  for (const result of results) {
    if (result.status === 'rejected') {
      metrics.push({ label: 'flow', ms: 0, ok: false, status: 0, error: result.reason?.message || String(result.reason) });
    }
  }

  const ok = metrics.filter(m => m.ok);
  const failed = metrics.filter(m => !m.ok);
  const byLabel = Object.groupBy ? Object.groupBy(ok, m => m.label) : ok.reduce((a, m) => ((a[m.label] ||= []).push(m), a), {});
  console.log(JSON.stringify({
    baseUrl,
    restaurants: slugList.length || restaurants,
    slugs: slugList,
    durationSeconds,
    concurrency,
    orderEvery,
    requests: metrics.length,
    failures: failed.length,
    summary: Object.fromEntries(Object.entries(byLabel).map(([label, rows]) => {
      const times = rows.map(r => r.ms).sort((a, b) => a - b);
      return [label, {
        count: rows.length,
        avgMs: Math.round(times.reduce((a, b) => a + b, 0) / times.length),
        p95Ms: times[Math.floor(times.length * 0.95)] || 0,
        maxMs: times[times.length - 1] || 0,
      }];
    })),
    failed: failed.slice(0, 20),
  }, null, 2));

  if (failed.length > 0) process.exitCode = 1;
}

main().catch(error => {
  console.error(error);
  process.exit(1);
});
