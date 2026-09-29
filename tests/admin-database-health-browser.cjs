const assert = require('node:assert/strict');
const fs = require('node:fs');
const {chromium} = require(process.env.QR_PLAYWRIGHT || 'playwright');

(async () => {
    const view = fs.readFileSync('resources/views/admin/database.blade.php', 'utf8');
    const scriptTag = [...view.matchAll(/<script>([\s\S]*?)<\/script>/g)].at(-1);
    assert(scriptTag, 'admin database view contains its health panel script');
    const script = scriptTag[1].replace("@json(route('admin.database.health'))", JSON.stringify('/admin/database/health'));

    const browser = await chromium.launch({
        ...(process.platform === 'win32' ? {channel: 'msedge'} : {}), headless: true,
    });
    try {
        const page = await browser.newPage();
        page.on('pageerror', error => console.error('Browser script error:', error.message));
        page.on('console', message => { if (message.type() === 'error') console.error('Browser console error:', message.text()); });
        const ids = ['db-health-state', 'db-health-hourly', 'db-health-hourly-detail', 'db-health-size', 'db-health-size-detail', 'db-health-current', 'db-health-current-detail', 'db-health-peak', 'db-health-peak-detail', 'db-health-tables', 'db-health-alerts', 'db-health-scope'];
        const sample = {
            available: true, sampled_at: '2026-09-30T12:00:00Z', scope: 'database_account',
            connection_count: 124, connection_window_seconds: 3600, connection_window_full_hour: true,
            connection_limit: 500, connection_percent: 24.8, current_connections: 3,
            max_used_connections: 10, max_connections: 150, aborted_connects: 0,
            database_bytes: 300_000_000, database_size_limit_bytes: 3_000_000_000,
            database_size_percent: 10, table_count: 14, estimated_rows: 1200,
            alerts: [], notice: 'Account-level sample.',
        };
        const mock = `<script>Object.defineProperty(document,'visibilityState',{configurable:true,get:()=> 'visible'});window.fetch=async()=>({ok:true,json:async()=>(${JSON.stringify(sample)})});</script>`;
        await page.setContent('<!doctype html><html><body>' + mock + ids.map(id => `<div id="${id}"></div>`).join('') + `<script>${script}</script></body></html>`);
        await page.waitForFunction(() => document.getElementById('db-health-hourly').textContent.includes('124'), null, {timeout: 5000});

        assert.equal(await page.locator('#db-health-hourly').textContent(), '124 / 500');
        assert.match(await page.locator('#db-health-size-detail').textContent(), /10(?:\.0)?% of configured 3\.00 GB/);
        assert.equal(await page.locator('#db-health-current').textContent(), '3');
        assert.equal(await page.locator('#db-health-peak').textContent(), '10 / 150');
        assert.equal(await page.locator('#db-health-state').textContent(), 'Monitoring');
        console.log('Admin database health panel renders account connections, DB size, and server stats correctly.');
    } finally {
        await browser.close();
    }
})().catch(error => {console.error(error);process.exitCode = 1;});
