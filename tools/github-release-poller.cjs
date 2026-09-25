const { spawn } = require('node:child_process');

const baseUrl = (process.env.STAGING_BASE_URL || '').replace(/\/$/, '');
const secret = process.env.CALLBACK_SECRET || '';
if (!baseUrl || !secret) throw new Error('STAGING_BASE_URL and CALLBACK_SECRET are required.');

(async () => {
  const response = await fetch(`${baseUrl}/release-test/next`, {
    headers: { accept: 'application/json', 'x-zemtab-callback-secret': secret },
  });
  if (response.status === 204) process.exit(0);
  if (!response.ok) throw new Error(`Fallback queue returned HTTP ${response.status}`);
  const payload = await response.json();
  if (!payload.run) {
    console.log('No fallback release test is queued.');
    return;
  }

  const child = spawn('node', ['tools/github-release-runner.cjs'], {
    stdio: 'inherit',
    env: {
      ...process.env,
      RUN_ID: payload.run.run_id,
      CALLBACK_URL: payload.run.callback_url,
      BASE_URL: payload.run.base_url,
    },
  });
  child.on('exit', code => { process.exitCode = code || 0; });
})().catch(error => {
  console.error(error.stack || error.message);
  process.exitCode = 1;
});
