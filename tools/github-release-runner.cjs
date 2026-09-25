const runId = process.env.RUN_ID;
const callbackUrl = process.env.CALLBACK_URL;
const callbackSecret = process.env.CALLBACK_SECRET;
const baseUrl = (process.env.BASE_URL || '').replace(/\/$/, '');

if (!runId || !callbackUrl || !callbackSecret || !baseUrl) {
  throw new Error('Missing runner environment.');
}

async function callback(payload) {
  const response = await fetch(callbackUrl, {
    method: 'POST',
    headers: {
      'content-type': 'application/json',
      'accept': 'application/json',
      'x-zemtab-callback-secret': callbackSecret,
    },
    body: JSON.stringify({ ...payload, run_id: runId }),
  });
  if (!response.ok) throw new Error(`Callback failed with HTTP ${response.status}`);
}

(async () => {
  try {
    await callback({status: 'running', phase: 'External runner connected', progress: 5});
    const started = Date.now();
    const response = await fetch(`${baseUrl}/ready`, {headers: {accept: 'application/json'}});
    if (!response.ok) throw new Error(`Staging readiness returned HTTP ${response.status}`);
    await callback({
      status: 'failed',
      phase: 'Runner connected; test phases not installed yet',
      progress: 5,
      error: 'The external connector is working. The full browser/capacity phases will be enabled in the next runner update.',
      report: {runner_connection: {ok: true, readiness_status: response.status, readiness_ms: Date.now() - started}},
    });
  } catch (error) {
    await callback({status: 'failed', phase: 'External runner failed', progress: 0, error: error.message});
    process.exitCode = 1;
  }
})();
