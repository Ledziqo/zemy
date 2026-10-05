#!/usr/bin/env node

/**
 * Bounded 10-minute guest-load test for isolated staging deployments.
 * Requires 1..500 seeded zt-stress-NNN venues with active tables/menu items.
 */

const fs = require('node:fs');
const baseUrl = (process.env.ZEMTAB_BASE_URL || '').replace(/\/$/, '');
const durationSeconds = Number(process.env.ZEMTAB_STRESS_DURATION_SECONDS || 600);
const venueCount = Number(process.env.ZEMTAB_STRESS_VENUES || 500);
const maxGuests = Number(process.env.ZEMTAB_STRESS_MAX_GUESTS || 2000);
const requestTimeoutMs = Number(process.env.ZEMTAB_TIMEOUT_MS || 15000);
const p95LimitMs = Number(process.env.ZEMTAB_STRESS_P95_LIMIT_MS || 3000);
const p95WindowsToFail = Number(process.env.ZEMTAB_STRESS_P95_WINDOWS_TO_FAIL || 2);
const maxRateLimitedPercent = Number(process.env.ZEMTAB_STRESS_MAX_429_PERCENT || 5);
const thinkMinMs = Number(process.env.ZEMTAB_STRESS_THINK_MIN_MS || 20000);
const thinkMaxMs = Number(process.env.ZEMTAB_STRESS_THINK_MAX_MS || 60000);
const staffScreens = Number(process.env.ZEMTAB_STRESS_STAFF_SCREENS || 50);
const staffLoginSpacingMs = Number(process.env.ZEMTAB_STRESS_STAFF_LOGIN_SPACING_MS || 7000);
const staffPollIntervalMs = Number(process.env.ZEMTAB_STRESS_STAFF_POLL_INTERVAL_MS || 30000);
const rampPlan = [100, 250, 500, 750, 1000, 1250, 1500, 1750, 2000]
  .map((guests, index) => ({ atSeconds: index * 60, guests: Math.min(guests, maxGuests) }))
  .filter((step, index, rows) => index === 0 || step.guests > rows[index - 1].guests);

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
async function sleepWhileActive(ms, stopAt = Infinity) {
  const end = Math.min(Date.now() + ms, stopAt);
  while (!state.failed && Date.now() < end) await sleep(Math.min(1000, end - Date.now()));
}
const csrf = html => html.match(/name="_token"\s+value="([^"]+)"/)?.[1] || html.match(/<meta name="csrf-token" content="([^"]+)"/)?.[1];
const firstItemId = html => html.match(/add\(\{\s*id:\s*(\d+)/)?.[1] || html.match(/name="items\[0\]\[id\]"\s+value="(\d+)"/)?.[1];
const firstProfileId = html => html.match(/name="profile_id"[^>]*value="(\d+)"/)?.[1] || html.match(/value="(\d+)"[^>]*name="profile_id"/)?.[1];
function extractPollUrl(html) {
  const match = html.match(/pollUrl:\s*("(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*')\s*,/s);
  if (!match) return null;
  if (match[1].startsWith('"')) return JSON.parse(match[1]);
  return match[1].slice(1, -1)
    .replace(/\\u([0-9a-fA-F]{4})/g, (_, hex) => String.fromCharCode(parseInt(hex, 16)))
    .replace(/\\\//g, '/')
    .replace(/\\'/g, "'")
    .replace(/\\\\/g, '\\');
}
const slugFor = index => `zt-stress-${String((index % venueCount) + 1).padStart(3, '0')}`;
const tableFor = index => String((index % 10) + 1);
const metrics = [];
const state = { failed: false, failure: null, rateLimited: 0, functionalErrors: 0, consecutiveSlowWindows: 0, consecutiveThrottleWindows: 0, staffSessionsStarted: 0 };

function record(label, status, ms, extra = {}) {
  metrics.push({ label, status, ms, at: Date.now(), ...extra });
}

async function request(url, options = {}, jar = new Jar(), label = 'request') {
  const headers = { ...(options.headers || {}) };
  const cookie = jar.header();
  if (cookie) headers.cookie = cookie;
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), requestTimeoutMs);
  const started = Date.now();
  try {
    const response = await fetch(url, {
      redirect: options.followRedirects === false ? 'manual' : 'follow',
      ...options,
      headers,
      signal: controller.signal,
    });
    jar.store(response.headers);
    record(label, response.status, Date.now() - started);
    if (response.status === 429) state.rateLimited++;
    if (response.status >= 500) fail(`${label} returned HTTP ${response.status}`);
    return response;
  } catch (error) {
    const message = error.name === 'AbortError' ? `timeout after ${requestTimeoutMs}ms on ${label}` : `${label} request failed: ${error.message}`;
    record(label, 0, Date.now() - started, { error: message });
    fail(message);
    throw error;
  } finally {
    clearTimeout(timeout);
  }
}

function fail(message) {
  if (state.failed) return;
  state.failed = true;
  state.failure = message;
  console.error(`\nLOAD STOP: ${message}`);
}

function percentile(values, p) {
  if (!values.length) return 0;
  const sorted = [...values].sort((a, b) => a - b);
  return sorted[Math.min(sorted.length - 1, Math.floor(sorted.length * p))];
}

function snapshot(rows) {
  const byLabel = {};
  for (const label of [...new Set(rows.map(row => row.label))]) {
    const group = rows.filter(row => row.label === label);
    const ok = group.filter(row => (row.status >= 200 && row.status < 400) || row.status === 304);
    const throttled = group.filter(row => row.status === 429);
    const failed = group.filter(row => row.status === 0 || row.status >= 500 || (row.status >= 400 && row.status !== 429));
    byLabel[label] = {
      count: group.length,
      ok: ok.length,
      rateLimited: throttled.length,
      errors: failed.length,
      p50Ms: percentile(ok.map(row => row.ms), 0.50),
      p95Ms: percentile(ok.map(row => row.ms), 0.95),
      p99Ms: percentile(ok.map(row => row.ms), 0.99),
      maxMs: ok.length ? Math.max(...ok.map(row => row.ms)) : 0,
    };
  }
  return { requests: rows.length, byLabel };
}

async function pauseForRateLimit(response, stopAt = Infinity) {
  const header = response.headers.get('retry-after');
  const retrySeconds = header && /^\d+$/.test(header) ? Number(header) : 2;
  await response.arrayBuffer();
  await sleepWhileActive(Math.min(600000, Math.max(1000, retrySeconds * 1000)), stopAt);
  return Date.now() < stopAt && !state.failed;
}

function requireSuccess(response, label, accepted = [200]) {
  if (response.status === 429) return false;
  if (!accepted.includes(response.status)) {
    state.functionalErrors++;
    fail(`${label} returned unexpected HTTP ${response.status}`);
    return false;
  }
  return true;
}

async function checkedStaffRequest(url, options, jar, label, stopAt, accepted = [200]) {
  for (let attempt = 0; attempt < 4; attempt++) {
    const response = await request(url, options, jar, label);
    if (response.status === 429 && attempt < 3) {
      if (!await pauseForRateLimit(response, stopAt)) throw new Error('rate-limit cooldown extends beyond the test window');
      continue;
    }
    if (!accepted.includes(response.status)) {
      state.functionalErrors++;
      fail(`${label} returned unexpected HTTP ${response.status}`);
    }
    return response;
  }
}

async function loginStaffSession(venueIndex, stopAt) {
  const jar = new Jar();
  const loginPage = await checkedStaffRequest(`${baseUrl}/login`, {}, jar, 'staff-login-page', stopAt);
  const loginHtml = await loginPage.text();
  const loginToken = csrf(loginHtml);
  if (!loginToken) throw new Error('staff login page did not contain a CSRF token');

  const slug = slugFor(venueIndex);
  const loginBody = new URLSearchParams({ _token: loginToken, email: `${slug}@zemtab.test`, password: 'password' });
  const login = await checkedStaffRequest(`${baseUrl}/login`, {
    method: 'POST', headers: { 'content-type': 'application/x-www-form-urlencoded' }, body: loginBody, followRedirects: false,
  }, jar, 'staff-login', stopAt, [200, 302, 303]);
  await login.arrayBuffer();
  if (state.failed) throw new Error(state.failure);

  const profilePage = await checkedStaffRequest(`${baseUrl}/restaurant/profile-select`, {}, jar, 'staff-profile-page', stopAt);
  const profileHtml = await profilePage.text();
  const profileToken = csrf(profileHtml);
  const profileId = firstProfileId(profileHtml);
  if (!profileToken || !profileId) throw new Error('staff profile page did not contain its CSRF token and profile id');

  const profileBody = new URLSearchParams({ _token: profileToken, profile_id: profileId, password: 'password' });
  const profileLogin = await checkedStaffRequest(`${baseUrl}/restaurant/profile-login`, {
    method: 'POST', headers: { 'content-type': 'application/x-www-form-urlencoded' }, body: profileBody, followRedirects: false,
  }, jar, 'staff-profile-login', stopAt, [200, 302, 303]);
  await profileLogin.arrayBuffer();
  if (state.failed) throw new Error(state.failure);

  const workboard = await checkedStaffRequest(`${baseUrl}/restaurant/orders`, {}, jar, 'staff-workboard', stopAt);
  const workboardHtml = await workboard.text();
  const pollUrl = extractPollUrl(workboardHtml);
  if (!pollUrl) throw new Error('staff Work Board did not expose its signed poll URL');
  return { jar, pollUrl, venueIndex };
}

async function staffWorkboardLoop(session, stopAt) {
  let revision = null;
  await sleepWhileActive(Math.random() * staffPollIntervalMs, stopAt);
  while (Date.now() < stopAt && !state.failed) {
    const headers = { accept: 'application/json', 'x-requested-with': 'XMLHttpRequest' };
    if (revision) headers['x-board-revision'] = revision;
    try {
      const response = await request(session.pollUrl, { headers, followRedirects: false }, session.jar, 'staff-poll');
      if (response.status === 429) {
        await pauseForRateLimit(response, stopAt);
      } else if (response.status === 304) {
        revision = response.headers.get('x-board-revision') || revision;
        await response.arrayBuffer();
      } else if (requireSuccess(response, 'staff Work Board poll', [200])) {
        const data = await response.json();
        revision = response.headers.get('x-board-revision') || data.revision || revision;
      } else {
        await response.arrayBuffer();
      }
    } catch (error) {
      if (!state.failed) {
        state.functionalErrors++;
        fail(`staff Work Board poll failed for ${slugFor(session.venueIndex)}: ${error.message}`);
      }
      return;
    }
    await sleepWhileActive(staffPollIntervalMs + Math.random() * 5000, stopAt);
  }
}

async function guestLoop(guestIndex, stopAt) {
  const jar = new Jar();
  const slug = slugFor(guestIndex);
  const table = tableFor(guestIndex);
  const menuUrl = `${baseUrl}/r/${slug}/table/${table}`;
  let cycle = 0;
  await sleepWhileActive(Math.random() * 10000, stopAt);

  while (Date.now() < stopAt && !state.failed) {
    try {
      const menu = await request(menuUrl, {}, jar, 'menu');
      if (menu.status === 429) {
        await pauseForRateLimit(menu, stopAt);
        continue;
      }
      if (!requireSuccess(menu, 'menu')) {
        await menu.arrayBuffer();
        break;
      }
      const html = await menu.text();
      const token = csrf(html);
      const itemId = firstItemId(html);
      if (!token || !itemId) {
        state.functionalErrors++;
        fail(`menu for ${slug} did not contain a CSRF token and available item`);
        break;
      }

      const behavior = Math.random();
      if (behavior >= 0.70 && behavior < 0.85) {
        const body = new URLSearchParams();
        body.set('_token', token);
        body.set('items[0][id]', itemId);
        body.set('items[0][quantity]', '1');
        body.set('items[0][note]', '');
        body.set('note', '10-minute staging stress test');
        body.set('client_request_id', `quick-${slug}-${guestIndex}-${cycle}-${Date.now()}`);
        const order = await request(`${menuUrl}/orders`, {
          method: 'POST', headers: { 'content-type': 'application/x-www-form-urlencoded' }, body, followRedirects: false,
        }, jar, 'order');
        if (order.status === 429) await pauseForRateLimit(order, stopAt);
        else if (requireSuccess(order, 'order', [200, 302, 303])) {
          await order.arrayBuffer();
          const confirmation = await request(`${menuUrl}/confirmation`, {}, jar, 'confirmation');
          if (confirmation.status === 429) await pauseForRateLimit(confirmation, stopAt);
          else if (requireSuccess(confirmation, 'confirmation')) await confirmation.arrayBuffer();
          else await confirmation.arrayBuffer();
        } else await order.arrayBuffer();
      } else if (behavior >= 0.85 && behavior < 0.90) {
        const body = new URLSearchParams({ _token: token, type: 'call_waiter', note: '10-minute staging stress test' });
        const service = await request(`${menuUrl}/service-requests`, {
          method: 'POST', headers: { 'content-type': 'application/x-www-form-urlencoded' }, body, followRedirects: false,
        }, jar, 'service-request');
        if (service.status === 429) await pauseForRateLimit(service, stopAt);
        else requireSuccess(service, 'service request', [200, 302, 303]);
        await service.arrayBuffer();
      } else if (behavior >= 0.90) {
        await sleep(1000 + Math.random() * 3000);
        const refresh = await request(menuUrl, {}, jar, 'menu-refresh');
        if (refresh.status === 429) await pauseForRateLimit(refresh, stopAt);
        else if (!requireSuccess(refresh, 'menu refresh')) await refresh.arrayBuffer();
        else await refresh.arrayBuffer();
      }
    } catch (error) {
      if (!state.failed) fail(`guest ${guestIndex + 1}: ${error.message}`);
      break;
    }
    cycle++;
    await sleepWhileActive(thinkMinMs + Math.random() * Math.max(0, thinkMaxMs - thinkMinMs), stopAt);
  }
}

async function recoveryProbe() {
  const checks = [];
  for (const path of ['/ready', '/login']) {
    const started = Date.now();
    try {
      const response = await fetch(`${baseUrl}${path}`, { signal: AbortSignal.timeout(requestTimeoutMs) });
      await response.arrayBuffer();
      checks.push({ path, status: response.status, ms: Date.now() - started, ok: response.ok });
    } catch (error) {
      checks.push({ path, status: 0, ms: Date.now() - started, ok: false, error: error.message });
    }
  }
  return { ok: checks.every(check => check.ok), checks };
}

async function main() {
  if (!baseUrl) throw new Error('Set ZEMTAB_BASE_URL to the isolated staging site.');
  const parsed = new URL(baseUrl);
  if (!['http:', 'https:'].includes(parsed.protocol) || ['zemtab.com', 'www.zemtab.com'].includes(parsed.hostname.toLowerCase())) {
    throw new Error('Refusing to load-test a production ZemTab hostname.');
  }
  if (!Number.isInteger(durationSeconds) || durationSeconds !== 600) throw new Error('This profile is fixed to a 10-minute (600 second) load window.');
  if (!Number.isInteger(venueCount) || venueCount < 1 || venueCount > 500) throw new Error('ZEMTAB_STRESS_VENUES must be between 1 and 500.');
  if (!Number.isInteger(maxGuests) || maxGuests < 100 || maxGuests > 2000) throw new Error('ZEMTAB_STRESS_MAX_GUESTS must be between 100 and 2000.');
  if (!Number.isFinite(p95LimitMs) || p95LimitMs < 1 || !Number.isInteger(p95WindowsToFail) || p95WindowsToFail < 1 || !Number.isFinite(maxRateLimitedPercent) || maxRateLimitedPercent < 0 || maxRateLimitedPercent > 100) throw new Error('Load stop thresholds must be valid.');
  if (!Number.isInteger(staffScreens) || staffScreens < 0 || staffScreens > venueCount || !Number.isInteger(staffLoginSpacingMs) || staffLoginSpacingMs < 5000 || !Number.isInteger(staffPollIntervalMs) || staffPollIntervalMs < 10000) throw new Error('Staff screen count/intervals are outside the safe test profile.');

  console.log(`Target: ${baseUrl}`);
  console.log(`Load window: 10 minutes | seeded venues: ${venueCount} | max guest sessions: ${maxGuests}`);
  console.log(`Staff workload: ${staffScreens} independently authenticated Work Boards | login spacing: ${staffLoginSpacingMs}ms | poll interval: ${staffPollIntervalMs}ms`);
  console.log(`Traffic mix: 70% browse, 15% order + confirmation, 5% service request, 10% refresh`);
  console.log(`Failure stops: HTTP 5xx/timeouts, functional errors, p95 > ${p95LimitMs}ms, or 429s > ${maxRateLimitedPercent}% for ${p95WindowsToFail} consecutive 30s windows`);

  const startedAt = Date.now();
  const stopAt = startedAt + durationSeconds * 1000;
  const workers = [];
  const rampResults = [];
  let spawned = 0;
  let planIndex = 0;
  let staffPreparation;

  const latencyMonitor = setInterval(() => {
    const cutoff = Date.now() - 30000;
    const recent = metrics.filter(row => row.at >= cutoff && row.status >= 200 && row.status < 400);
    const allRecent = metrics.filter(row => row.at >= cutoff);
    const p95Ms = percentile(recent.map(row => row.ms), 0.95);
    if (recent.length >= 30 && p95Ms > p95LimitMs) state.consecutiveSlowWindows++;
    else state.consecutiveSlowWindows = 0;
    const throttlePercent = allRecent.length ? (allRecent.filter(row => row.status === 429).length / allRecent.length) * 100 : 0;
    if (allRecent.length >= 30 && throttlePercent > maxRateLimitedPercent) state.consecutiveThrottleWindows++;
    else state.consecutiveThrottleWindows = 0;
    if (state.consecutiveSlowWindows >= p95WindowsToFail) fail(`successful-request p95 exceeded ${p95LimitMs}ms for ${p95WindowsToFail} consecutive windows`);
    if (state.consecutiveThrottleWindows >= p95WindowsToFail) fail(`HTTP 429 rate exceeded ${maxRateLimitedPercent}% for ${p95WindowsToFail} consecutive windows`);
    const elapsedSeconds = Math.min(durationSeconds, Math.floor((Date.now() - startedAt) / 1000));
    console.log(`LOAD_PROGRESS elapsed=${elapsedSeconds} guests=${spawned} staff=${state.staffSessionsStarted} requests=${metrics.length}`);
  }, 30000);

  try {
    staffPreparation = (async () => {
      for (let index = 0; index < staffScreens && !state.failed && Date.now() < stopAt; index++) {
        try {
          const session = await loginStaffSession(index, stopAt);
          state.staffSessionsStarted++;
          workers.push(staffWorkboardLoop(session, stopAt));
          if (state.staffSessionsStarted % 10 === 0 || state.staffSessionsStarted === staffScreens) {
            console.log(`Authenticated staff Work Boards: ${state.staffSessionsStarted}/${staffScreens}`);
          }
          await sleepWhileActive(staffLoginSpacingMs, stopAt);
        } catch (error) {
          if (!state.failed) {
            state.functionalErrors++;
            fail(`staff setup failed for ${slugFor(index)}: ${error.message}`);
          }
          break;
        }
      }
    })();

    while (planIndex < rampPlan.length && !state.failed) {
      const step = rampPlan[planIndex];
      const dueAt = startedAt + step.atSeconds * 1000;
      if (Date.now() < dueAt) await sleepWhileActive(dueAt - Date.now(), stopAt);
      if (state.failed || Date.now() >= stopAt) break;
      while (spawned < step.guests) {
        workers.push(guestLoop(spawned++, stopAt));
      }
      rampResults.push({ elapsedSeconds: Math.floor((Date.now() - startedAt) / 1000), targetGuestSessions: spawned, requestsSoFar: metrics.length });
      console.log(`  +${Math.floor((Date.now() - startedAt) / 1000)}s: ${spawned} guest sessions | ${metrics.length} requests | ${state.rateLimited} rate-limited`);
      planIndex++;
    }
    while (Date.now() < stopAt && !state.failed) await sleep(Math.min(1000, stopAt - Date.now()));
  } finally {
    clearInterval(latencyMonitor);
  }

  await staffPreparation;
  await Promise.allSettled(workers);
  if (!state.failed && state.staffSessionsStarted < staffScreens) {
    state.functionalErrors++;
    fail(`only ${state.staffSessionsStarted}/${staffScreens} staff Work Boards authenticated before the test window ended`);
  }
  const recovery = await recoveryProbe();
  const summary = snapshot(metrics);
  const lastLoadAt = metrics.length ? Math.max(...metrics.map(row => row.at)) : startedAt;
  const actualLoadSeconds = Math.min(durationSeconds, Math.max(0, (lastLoadAt - startedAt) / 1000));
  const highestGuests = Math.max(0, ...rampResults.map(row => row.targetGuestSessions));
  const targetReached = highestGuests === maxGuests;
  const bucketCounts = new Map();
  for (const row of metrics) {
    const bucket = Math.floor((row.at - startedAt) / 30000);
    bucketCounts.set(bucket, (bucketCounts.get(bucket) || 0) + 1);
  }
  const maxThirtySecondRequests = Math.max(0, ...bucketCounts.values());
  const report = {
    target: baseUrl,
    startedAt: new Date(startedAt).toISOString(),
    finishedAt: new Date().toISOString(),
    requestedLoadWindowSeconds: durationSeconds,
    actualLoadWindowSeconds: Number(actualLoadSeconds.toFixed(1)),
    seededVenues: venueCount,
    trafficModel: {
      guestSessionsPerVenueAtPeak: Number((highestGuests / venueCount).toFixed(2)),
      maximumGuestSessions: maxGuests,
      rampUpSeconds: 480,
      peakHoldSeconds: 120,
      rampPlan,
      thinkTimeSeconds: [thinkMinMs / 1000, thinkMaxMs / 1000],
      mixPercent: { browse: 70, orderAndConfirmation: 15, serviceRequest: 5, refresh: 10 },
      staffWorkBoardSessions: state.staffSessionsStarted,
      requestedStaffWorkBoardSessions: staffScreens,
      staffLoginSpacingSeconds: staffLoginSpacingMs / 1000,
      staffPollIntervalSeconds: staffPollIntervalMs / 1000,
    },
    ramp: rampResults,
    summary,
    throughput: {
      averageRequestsPerSecond: Number((summary.requests / Math.max(1, actualLoadSeconds)).toFixed(2)),
      peakThirtySecondRequestsPerSecond: Number((maxThirtySecondRequests / 30).toFixed(2)),
    },
    rateLimitedRequests: state.rateLimited,
    rateLimitedPercent: Number((summary.requests ? (state.rateLimited / summary.requests) * 100 : 0).toFixed(2)),
    functionalErrors: state.functionalErrors,
    failure: state.failure,
    reachedConfiguredLoadCeiling: targetReached,
    recovery,
    result: !state.failed && recovery.ok ? (targetReached ? 'completed-at-configured-load-ceiling' : 'completed-before-load-ceiling') : 'stopped-on-failure',
    interpretation: 'A pass means this guest workload completed without the configured saturation signals. It is not a capacity guarantee and does not measure fresh database connections directly.',
  };

  if (process.env.ZEMTAB_REPORT_PATH) fs.writeFileSync(process.env.ZEMTAB_REPORT_PATH, JSON.stringify(report, null, 2));
  console.log(`\nResult: ${report.result} | guests ${highestGuests}/${maxGuests} | requests ${summary.requests} | rate-limited ${state.rateLimited}`);
  for (const [label, values] of Object.entries(summary.byLabel)) {
    console.log(`  ${label}: ${values.count} requests, p95 ${values.p95Ms}ms, errors ${values.errors}, 429s ${values.rateLimited}`);
  }
  console.log(`Recovery: ${recovery.ok ? 'healthy' : 'failed'}`);
  if (state.failure) console.log(`Stop reason: ${state.failure}`);
  if (state.failed || !recovery.ok) process.exitCode = 1;
}

main().catch(error => {
  console.error(`Fatal: ${error.message}`);
  process.exitCode = 1;
});
