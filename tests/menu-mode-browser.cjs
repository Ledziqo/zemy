const assert = require('node:assert/strict');
const fs = require('node:fs');
const {execFileSync} = require('node:child_process');
const {chromium} = require(process.env.QR_PLAYWRIGHT || 'playwright');

(async () => {
    const result = execFileSync('php', ['tests/menu-mode.php', '--preview'], {encoding: 'utf8'});
    assert(/Passed \d+ menu-mode checks/.test(result), result);
    const browser = await chromium.launch({...(process.platform === 'win32' ? {channel: 'msedge'} : {}), headless: true});
    try {
        for (const mode of ['menu_only', 'full']) for (const appearance of ['photos', 'text']) {
            const page = await browser.newPage({viewport: {width: 375, height: 812}});
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => route.abort());
            let html = fs.readFileSync(`tmp/menu-mode-preview/${mode}-${appearance}.html`, 'utf8');
            html = html.replace(/<script\b[^>]*\bsrc=[^>]*>[\s\S]*?<\/script>/gi, '')
                .replace(/<link\b[^>]*>/gi, '');
            await page.setContent(html);
            await page.addStyleTag({content: fs.readFileSync('public/assets/app.css', 'utf8')});
            await page.addScriptTag({content: fs.readFileSync('public/assets/alpine.min.js', 'utf8')});
            const heading = page.getByRole('heading', {name: 'Soup', exact: true});
            if (mode === 'menu_only') {
                await heading.waitFor();
                assert.equal(await page.getByRole('button', {name: /Cart|Place order|Request Bill|Call Staff/}).count(), 0);
                await page.getByRole('searchbox').fill('missing dish');
                await page.waitForFunction(() => document.querySelector('article').style.display === 'none');
                await page.getByRole('searchbox').fill('soup');
                await heading.waitFor({state: 'visible'});
                await page.getByRole('button', {name: 'Food', exact: true}).click();
                await heading.waitFor({state: 'visible'});
            } else {
                await page.getByRole('button', {name: /View details.*order/}).click();
                await page.getByRole('heading', {name: 'Soup', exact: true}).waitFor({state: 'visible'});
                assert.equal(await page.locator('.relative.aspect-\\[4\\/3\\]').count(), appearance === 'photos' ? 1 : 0);
            }
            assert.equal(await page.locator('img[src*="test-photo.jpg"]').count() > 0, appearance === 'photos');
            for (const width of [375, 1280]) {
                await page.setViewportSize({width, height: 900});
                assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${mode}/${appearance} overflows at ${width}px`);
            }
            assert.deepEqual(errors, [], `${mode}/${appearance} JavaScript errors`);
            if (mode === 'menu_only') {
                await page.setViewportSize({width: 375, height: 812});
                await page.screenshot({path: `tmp/menu-mode-preview/${appearance}.png`, fullPage: true});
            }
            await page.close();
        }
        console.log('Passed browser checks: search/category filtering, ordering modal, photo toggles, mobile/desktop widths, and no JavaScript errors.');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exit(1);});
