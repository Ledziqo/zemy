const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { spawn } = require('node:child_process');

const runId = process.env.RUN_ID;
const callbackUrl = process.env.CALLBACK_URL;
const callbackSecret = process.env.CALLBACK_SECRET;
const baseUrl = (process.env.BASE_URL || '').replace(/\/$/, '');
const adminEmail = process.env.ADMIN_EMAIL || '';
const adminPassword = process.env.ADMIN_PASSWORD || '';
const requestTimeoutMs = Number(process.env.RELEASE_TEST_REQUEST_TIMEOUT_MS || 120000);

const missingEnvironment = [
  ['RUN_ID', runId],
  ['CALLBACK_URL', callbackUrl],
  ['CALLBACK_SECRET', callbackSecret],
  ['BASE_URL', baseUrl],
  ['ADMIN_EMAIL', adminEmail],
  ['ADMIN_PASSWORD', adminPassword],
].filter(([, value]) => !value).map(([name]) => name);

async function reportConfigurationFailure() {
  if (!runId || !callbackUrl || !callbackSecret) return;
  try {
    await fetch(callbackUrl, {
      method: 'POST',
      headers: { 'content-type': 'application/json', 'x-zemtab-callback-secret': callbackSecret },
      body: JSON.stringify({
        run_id: runId,
        status: 'failed',
        phase: 'External runner configuration is incomplete',
        progress: 0,
        error: `Missing GitHub Actions environment values: ${missingEnvironment.join(', ')}`,
      }),
    });
  } catch (_) {}
}

const targetHost = baseUrl ? new URL(baseUrl).hostname.toLowerCase() : '';
if (targetHost === 'zemtab.com' || targetHost === 'www.zemtab.com' || targetHost.endsWith('.zemtab.com')) {
  throw new Error('The complete release test is locked to a temporary/staging host and cannot target production.');
}

class Jar {
  constructor() { this.cookies = new Map(); }
  header() { return [...this.cookies.entries()].map(([key, value]) => `${key}=${value}`).join('; '); }
  store(headers) {
    const values = headers.getSetCookie ? headers.getSetCookie() : (headers.get('set-cookie') ? [headers.get('set-cookie')] : []);
    for (const line of values) {
      const [pair] = line.split(';');
      const index = pair.indexOf('=');
      if (index > 0) this.cookies.set(pair.slice(0, index), pair.slice(index + 1));
    }
  }
}

const csrf = html => html.match(/name="_token"\s+value="([^"]+)"/)?.[1]
  || html.match(/<meta name="csrf-token" content="([^"]+)"/)?.[1];
const firstItemId = html => html.match(/add\(\{\s*id:\s*(\d+)/)?.[1]
  || html.match(/name="items\[0\]\[id\]"\s+value="(\d+)"/)?.[1];
const firstProfileId = html => html.match(/name="profile_id"[^>]*value="(\d+)"/)?.[1]
  || html.match(/value="(\d+)"[^>]*name="profile_id"/)?.[1];
const extractPollUrl = html => {
  const match = html.match(/pollUrl:\s*("(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*')\s*,/s);
  if (!match) return null;
  if (match[1].startsWith('"')) return JSON.parse(match[1]);
  return match[1].slice(1, -1)
    .replace(/\\u([0-9a-fA-F]{4})/g, (_, hex) => String.fromCharCode(parseInt(hex, 16)))
    .replace(/\\\//g, '/')
    .replace(/\\'/g, "'")
    .replace(/\\\\/g, '\\');
};
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

async function request(url, options = {}, jar = new Jar()) {
  for (let attempt = 0; attempt < 4; attempt++) {
    const response = await requestOnce(url, options, jar);
    if (response.status !== 429 || attempt === 3) return response;
    const raw = response.headers.get('retry-after');
    const seconds = raw && /^\d+$/.test(raw) ? Number(raw) : raw ? (Date.parse(raw) - Date.now()) / 1000 : 60;
    const waitSeconds = Math.max(1, Number.isFinite(seconds) ? seconds : 60) + 2;
    if (waitSeconds > 602) return response;
    await response.text();
    console.log(`Rate limited on ${new URL(url).pathname}; waiting ${Math.ceil(waitSeconds)} seconds before retry ${attempt + 2}/4`);
    await sleep(waitSeconds * 1000);
  }
}

async function requestOnce(url, options = {}, jar = new Jar()) {
  const headers = { ...(options.headers || {}) };
  const cookie = jar.header();
  if (cookie) headers.cookie = cookie;
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), options.timeoutMs || requestTimeoutMs);
  try {
    const response = await fetch(url, {
      redirect: options.followRedirects === false ? 'manual' : 'follow',
      ...options,
      headers,
      signal: controller.signal,
    });
    jar.store(response.headers);
    return response;
  } finally {
    clearTimeout(timeout);
  }
}

async function callback(payload) {
  const response = await fetch(callbackUrl, {
    method: 'POST',
    headers: { 'content-type': 'application/json', accept: 'application/json', 'x-zemtab-callback-secret': callbackSecret },
    body: JSON.stringify({ ...payload, run_id: runId }),
  });
  if (!response.ok) throw new Error(`Callback failed with HTTP ${response.status}`);
}

async function phase(name, progress, work) {
  await callback({ status: 'running', phase: name, progress });
  return work();
}

async function adminLogin() {
  const jar = new Jar();
  const loginPage = await request(`${baseUrl}/login`, {}, jar);
  const loginHtml = await loginPage.text();
  if (!loginPage.ok) throw new Error(`login page returned HTTP ${loginPage.status}`);
  const loginToken = csrf(loginHtml);
  if (!loginToken) throw new Error('admin login page did not contain a CSRF token');
  const body = new URLSearchParams({ _token: loginToken, email: adminEmail, password: adminPassword });
  const login = await request(`${baseUrl}/login`, { method: 'POST', headers: { 'content-type': 'application/x-www-form-urlencoded' }, body, followRedirects: false }, jar);
  await login.text();
  if (![200, 302, 303].includes(login.status)) throw new Error(`admin login returned HTTP ${login.status}`);
  const database = await request(`${baseUrl}/admin/database`, {}, jar);
  const databaseHtml = await database.text();
  if (!database.ok || !databaseHtml.includes('Complete release test')) throw new Error(`admin database page returned HTTP ${database.status}`);
  const token = csrf(databaseHtml);
  if (!token) throw new Error('admin database page did not contain a CSRF token');
  return { jar, token };
}

async function setupRun(admin, fields) {
  const body = new URLSearchParams({ _token: admin.token, ...fields });
  const response = await request(`${baseUrl}/admin/setup-run`, { method: 'POST', headers: { 'content-type': 'application/x-www-form-urlencoded' }, body, followRedirects: false, timeoutMs: 180000 }, admin.jar);
  const text = await response.text();
  if (![200, 302, 303].includes(response.status)) throw new Error(`setup operation returned HTTP ${response.status}: ${text.slice(0, 300)}`);
  return text;
}

async function runServerScanWithThrottleRetry(admin) {
  for (let attempt = 1; attempt <= 3; attempt++) {
    const response = await request(`${baseUrl}/admin/database/full-scan`, {
      method: 'POST',
      headers: { 'content-type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ _token: admin.token }),
      followRedirects: false,
      timeoutMs: 180000,
    }, admin.jar);
    if (response.status !== 429 || attempt === 3) return response;
    const retryHeader = response.headers.get('retry-after');
    const retrySeconds = retryHeader && /^\d+$/.test(retryHeader)
      ? Number(retryHeader)
      : retryHeader ? Math.max(1, Math.ceil((Date.parse(retryHeader) - Date.now()) / 1000)) : 300;
    await response.text();
    await callback({ status: 'running', phase: `Server scan rate-limited; retrying in ${retrySeconds}s`, progress: 30 });
    await sleep((retrySeconds + 2) * 1000);
  }
}

async function seedBatches(admin) {
  const batches = Number(process.env.RELEASE_TEST_SEED_BATCHES || 10);
  for (let batch = 1; batch <= batches; batch++) {
    await setupRun(admin, { seed_stress_data: '1', stress_batch: String(batch) });
    await callback({ status: 'running', phase: `Seeded stress batch ${batch}/${batches}`, progress: 5 + Math.floor((batch / batches) * 20) });
  }
}

async function guestWorkflow() {
  const jar = new Jar();
  const menuUrl = `${baseUrl}/r/zt-stress-001/table/1`;
  const menu = await request(menuUrl, {}, jar);
  const html = await menu.text();
  if (!menu.ok) throw new Error(`guest menu returned HTTP ${menu.status}`);
  const token = csrf(html);
  const itemId = firstItemId(html);
  if (!token || !itemId) throw new Error('guest menu did not expose a CSRF token and item');
  const orderBody = new URLSearchParams({ _token: token, 'items[0][id]': itemId, 'items[0][quantity]': '1', 'items[0][note]': '', note: 'automated release test order', client_request_id: `release-test-${runId}-order` });
  const order = await request(`${menuUrl}/orders`, { method: 'POST', headers: { 'content-type': 'application/x-www-form-urlencoded' }, body: orderBody, followRedirects: false }, jar);
  await order.text();
  if (![200, 302, 303].includes(order.status)) throw new Error(`guest order returned HTTP ${order.status}`);
  const requestBody = new URLSearchParams({ _token: token, type: 'call_waiter', note: 'automated release test request' });
  const service = await request(`${menuUrl}/service-requests`, { method: 'POST', headers: { 'content-type': 'application/x-www-form-urlencoded' }, body: requestBody, followRedirects: false }, jar);
  await service.text();
  if (![200, 302, 303].includes(service.status)) throw new Error(`guest service request returned HTTP ${service.status}`);
  return { menuStatus: menu.status, orderStatus: order.status, serviceStatus: service.status };
}

async function runExternalCommand(command, args, env) {
  return new Promise((resolve, reject) => {
    const child = spawn(command, args, { env: { ...process.env, ...env }, stdio: ['ignore', 'pipe', 'pipe'] });
    let stdout = '';
    let stderr = '';
    child.stdout.on('data', chunk => { stdout += chunk.toString(); process.stdout.write(chunk); });
    child.stderr.on('data', chunk => { stderr += chunk.toString(); process.stderr.write(chunk); });
    child.on('error', reject);
    child.on('close', code => resolve({ code, stdout: stdout.slice(-20000), stderr: stderr.slice(-10000) }));
  });
}

async function main() {
  const report = { runId, target: baseUrl, startedAt: new Date().toISOString(), phases: [] };
  let admin = null;
  let cleanupError = null;
  try {
    await phase('Connecting to staging', 2, async () => {
      const response = await fetch(`${baseUrl}/ready`, { headers: { accept: 'application/json' } });
      if (!response.ok) throw new Error(`staging readiness returned HTTP ${response.status}`);
      report.phases.push({ name: 'readiness', ok: true, status: response.status });
    });
    await phase('Logging in and preparing disposable test data', 5, async () => {
      admin = await adminLogin();
      report.phases.push({ name: 'admin-login', ok: true });
    });
    await phase('Seeding all disposable test batches', 8, async () => {
      await seedBatches(admin);
      report.phases.push({ name: 'seed', ok: true, batches: Number(process.env.RELEASE_TEST_SEED_BATCHES || 10) });
    });
    await phase('Running server-side release scan and rollback checks', 30, async () => {
      const response = await runServerScanWithThrottleRetry(admin);
      const text = await response.text();
      if (!response.ok) throw new Error(`server-side release scan returned HTTP ${response.status}: ${text.slice(0, 400)}`);
      report.phases.push({ name: 'server-scan', ok: true, status: response.status, reportVisible: text.toLowerCase().includes('release') });
    });
    await phase('Testing guest menu, ordering, and service requests', 45, async () => {
      const guest = await guestWorkflow();
      report.phases.push({ name: 'http-functional', ok: true, guest });
    });
    await phase('Testing desktop and mobile browser interactions', 58, async () => {
      const browserReportPath = path.join(os.tmpdir(), `zemtab-browser-${runId}.json`);
      const installed = await runExternalCommand('npx', ['playwright', 'test', '--version'], {});
      if (installed.code !== 0) throw new Error(`Playwright is unavailable: ${installed.stderr}`);
      const browser = await runExternalCommand('node', [path.join(process.cwd(), 'tools', 'browser-release-test.cjs')], { BASE_URL: baseUrl, ADMIN_EMAIL: adminEmail, ADMIN_PASSWORD: adminPassword, REPORT_PATH: browserReportPath });
      const artifact = fs.existsSync(browserReportPath) ? JSON.parse(fs.readFileSync(browserReportPath, 'utf8')) : null;
      report.phases.push({ name: 'browser', ok: browser.code === 0, output: browser.stdout.slice(-8000), artifact });
      if (browser.code !== 0) throw new Error(`browser phase failed: ${browser.stderr || browser.stdout}`);
    });
    await phase('Running gradual capacity test last', 65, async () => {
      const capacityReportPath = path.join(os.tmpdir(), `zemtab-capacity-${runId}.json`);
      const capacity = await runExternalCommand('node', [path.join(process.cwd(), 'tools', 'capacity-10min.js')], { ZEMTAB_BASE_URL: baseUrl, ZEMTAB_REPORT_PATH: capacityReportPath });
      const artifact = fs.existsSync(capacityReportPath) ? JSON.parse(fs.readFileSync(capacityReportPath, 'utf8')) : null;
      report.phases.push({ name: 'capacity', ok: capacity.code === 0, output: capacity.stdout.slice(-12000), artifact });
      if (capacity.code !== 0) throw new Error(`capacity phase stopped before a passing stage: ${capacity.stderr || capacity.stdout.slice(-3000)}`);
    });
    await phase('Verifying the site recovers after the test', 88, async () => {
      const home = await fetch(`${baseUrl}/login`, { headers: { accept: 'text/html' } });
      if (!home.ok) throw new Error(`recovery login page returned HTTP ${home.status}`);
      report.phases.push({ name: 'recovery', ok: true, status: home.status });
    });
  } catch (error) {
    report.error = error.message;
  } finally {
    if (admin) {
      try {
        await callback({ status: 'running', phase: 'Cleaning up disposable test data', progress: 92 });
        await setupRun(admin, { cleanup_stress_data: '1' });
        report.cleanup = { ok: true };
      } catch (error) {
        cleanupError = error;
        report.cleanup = { ok: false, error: error.message };
      }
    } else {
      report.cleanup = { ok: false, error: 'Cleanup could not start because staging admin login failed.' };
    }
  }
  report.finishedAt = new Date().toISOString();
  const passed = !report.error && !cleanupError && report.phases.every(phaseResult => phaseResult.ok) && report.cleanup.ok;
  await callback({ status: passed ? 'completed' : 'failed', phase: passed ? 'Complete release test finished and cleanup verified' : 'Complete release test finished with failures', progress: 100, error: passed ? null : (report.error || cleanupError?.message || 'One or more phases failed.'), report });
  if (!passed) process.exitCode = 1;
}

if (missingEnvironment.length) {
  reportConfigurationFailure().finally(() => {
    console.error(`Missing GitHub Actions environment values: ${missingEnvironment.join(', ')}`);
    process.exitCode = 1;
  });
} else {
  main().catch(async error => {
    console.error(error.stack || error.message);
    try { await callback({ status: 'failed', phase: 'External runner failed', progress: 0, error: error.message }); } catch (_) {}
    process.exitCode = 1;
  });
}
