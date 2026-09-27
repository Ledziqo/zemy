const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');

(async () => {
  const runner = fs.readFileSync('tools/github-release-runner.cjs', 'utf8');
  assert.match(runner, /const response = await runServerScanWithThrottleRetry\(admin\);\s*const text = await response\.text\(\);/);
  assert.doesNotMatch(runner, /const response = scan\.response;/);
  const retry = runner.slice(runner.indexOf('async function request('), runner.indexOf('async function requestOnce('));
  let calls = 0;
  const waits = [];
  const sandbox = { URL, console, Jar: class {}, sleep: async ms => waits.push(ms), requestOnce: async () => {
    calls++;
    return new Response('', { status: calls < 3 ? 429 : 200, headers: { 'retry-after': '2' } });
  }};
  vm.createContext(sandbox);
  vm.runInContext(retry, sandbox);
  assert.equal((await sandbox.request('https://example.test/login')).status, 200);
  assert.equal(calls, 3);
  assert.deepEqual(waits, [4000, 4000]);
  const capacity = fs.readFileSync('tools/capacity-10min.js', 'utf8');
  const stage = capacity.slice(capacity.indexOf('async function runStage('), capacity.indexOf('async function main('));
  const pollCalls = [];
  const fixture = {
    console, Date, process: { stdout: { write() {} } }, setInterval: () => 1, clearInterval() {},
    staffScreensPerVenue: 2, loginConcurrency: 1, stageSeconds: 1, staffSessionCache: new Map(),
    mapLimit: async (items, limit, fn) => Promise.all(items.map(fn)),
    loginStaffSession: async () => ({ jar: {}, pollUrl: 'https://example.test/poll?signature=test' }),
    pollLoop: async session => { assert.ok(session.jar); assert.ok(session.pollUrl); pollCalls.push(session); },
    guestLoop: async () => {}, recoveryProbe: async () => ({ ok: true }),
    summarize: () => ({ errors: [], byLabel: { poll: { count: 2, p95: 10 } } }),
  };
  vm.createContext(fixture);
  vm.runInContext(stage, fixture);
  assert.equal((await fixture.runStage(1)).passed, true);
  assert.equal(pollCalls.length, 2);
  console.log('PASS: login retries respect cooldown; stage passes complete sessions to polling.');
})().catch(error => { console.error(error); process.exitCode = 1; });
