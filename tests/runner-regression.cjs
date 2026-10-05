const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');

(async () => {
  const runner = fs.readFileSync('tools/github-release-runner.cjs', 'utf8');
  assert.match(runner, /const response = await runServerScanWithThrottleRetry\(admin\);\s*const text = await response\.text\(\);/);
  assert.doesNotMatch(runner, /const response = scan\.response;/);
  assert.match(runner, /async function cleanupStressData\(admin\)[\s\S]*setup operation returned HTTP 419[\s\S]*const refreshedAdmin = await adminLogin\(\)[\s\S]*await setupRun\(refreshedAdmin, fields\)/);
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
  assert.match(capacity, /'10,20,40,60,80,100,150,200,300,400,500'/);
  assert.match(capacity, /one persistent cookie\/session per active venue/);
  assert.match(capacity, /orderAndConfirmation: 15/);
  assert.match(fs.readFileSync('config/stress.php', 'utf8'), /'max_restaurants'\s*=>\s*500/);
  const workflow = fs.readFileSync('.github/workflows/complete-release-test.yml', 'utf8');
  assert.match(workflow, /repository_dispatch:\s*\n\s*types:\s*\[zemtab-complete-release-test\]/);
  assert.equal((workflow.match(/RELEASE_TEST_SEED_BATCHES: 50/g) || []).length, 2);
  assert.equal((workflow.match(/ZEMTAB_STAGES: 10,20,40,60,80,100,150,200,300,400,500/g) || []).length, 2);
  assert.equal((workflow.match(/ZEMTAB_FINAL_STAGE_SECONDS: 600/g) || []).length, 2);
  assert.equal((workflow.match(/ZEMTAB_LOGIN_SPACING_MS: 7000/g) || []).length, 2);
  assert.match(capacity, /finalStageSeconds = Number\(process\.env\.ZEMTAB_FINAL_STAGE_SECONDS \|\| stageSeconds\)/);
  const stage = capacity.slice(capacity.indexOf('async function runStage('), capacity.indexOf('async function main('));
  const pollCalls = [];
  const fixture = {
    console, Date, process: { stdout: { write() {} } }, setInterval: () => 1, clearInterval() {},
    staffScreensPerVenue: 2, loginConcurrency: 1, stageSeconds: 1, finalStageSeconds: 1, stages: [1], staffSessionCache: new Map(),
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
