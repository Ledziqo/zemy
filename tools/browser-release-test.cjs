const { chromium, devices } = require('playwright');

const baseUrl = (process.env.BASE_URL || '').replace(/\/$/, '');
const guestSlug = process.env.GUEST_SLUG || 'zt-stress-001';
const adminEmail = process.env.ADMIN_EMAIL || '';
const adminPassword = process.env.ADMIN_PASSWORD || '';

if (!baseUrl || !adminEmail || !adminPassword) {
  throw new Error('BASE_URL, ADMIN_EMAIL, and ADMIN_PASSWORD are required.');
}

function assert(condition, message) {
  if (!condition) throw new Error(message);
}

async function checkContext(browser, name, contextOptions, results) {
  const context = await browser.newContext(contextOptions);
  const page = await context.newPage();
  const consoleErrors = [];
  const requestFailures = [];
  page.on('console', message => {
    if (message.type() === 'error') consoleErrors.push(message.text());
  });
  page.on('requestfailed', request => requestFailures.push(`${request.method()} ${request.url()} — ${request.failure()?.errorText || 'failed'}`));

  const started = Date.now();
  const response = await page.goto(`${baseUrl}/r/${guestSlug}/table/1`, { waitUntil: 'domcontentloaded', timeout: 30000 });
  assert(response && response.ok(), `${name}: menu returned HTTP ${response?.status()}`);
  await page.locator('h1').first().waitFor({ state: 'visible', timeout: 15000 });
  await page.getByText('Beef Tibs', { exact: true }).first().waitFor({ state: 'visible', timeout: 15000 });

  const categoryButtons = page.locator('nav button');
  assert(await categoryButtons.count() >= 2, `${name}: category navigation did not render`);
  await categoryButtons.nth(1).click();
  await page.waitForTimeout(100);

  const addButton = page.getByRole('button', { name: 'Add', exact: true }).first();
  await addButton.click();
  await page.getByText(/1 item\(s\)/).first().waitFor({ state: 'visible', timeout: 5000 });
  await page.getByRole('button', { name: /Cart/ }).first().click();
  await page.getByText('Checkout', { exact: true }).waitFor({ state: 'visible', timeout: 5000 });

  results.push({
    context: name,
    ok: true,
    elapsedMs: Date.now() - started,
    consoleErrors,
    requestFailures,
  });
  await context.close();
}

async function checkAdmin(browser, results) {
  const context = await browser.newContext();
  const page = await context.newPage();
  const consoleErrors = [];
  const requestFailures = [];
  page.on('console', message => {
    if (message.type() === 'error') consoleErrors.push(message.text());
  });
  page.on('requestfailed', request => requestFailures.push(`${request.method()} ${request.url()} — ${request.failure()?.errorText || 'failed'}`));

  const started = Date.now();
  await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await page.locator('input[name="email"]').fill(adminEmail);
  await page.locator('input[name="password"]').fill(adminPassword);
  await page.getByRole('button', { name: /sign in|login/i }).click();
  await page.waitForURL(/\/admin\/dashboard/, { timeout: 15000 });
  await page.goto(`${baseUrl}/admin/database`, { waitUntil: 'domcontentloaded', timeout: 30000 });
  assert((await page.locator('body').innerText()).includes('Complete release test'), 'admin database page did not render the release test control');

  results.push({
    context: 'admin desktop',
    ok: true,
    elapsedMs: Date.now() - started,
    consoleErrors,
    requestFailures,
  });
  await context.close();
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const results = [];
  try {
    await checkContext(browser, 'guest desktop', { viewport: { width: 1440, height: 900 } }, results);
    await checkContext(browser, 'guest mobile', { ...devices['Pixel 5'] }, results);
    await checkAdmin(browser, results);
    const failures = results.flatMap(result => [...result.consoleErrors, ...result.requestFailures]);
    const report = { baseUrl, results, ok: failures.length === 0 };
    if (process.env.REPORT_PATH) require('node:fs').writeFileSync(process.env.REPORT_PATH, JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
    if (!report.ok) process.exitCode = 1;
  } finally {
    await browser.close();
  }
})().catch(error => {
  console.error(`Browser release test failed: ${error.stack || error.message}`);
  process.exitCode = 1;
});
