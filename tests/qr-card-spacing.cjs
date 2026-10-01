const assert = require('node:assert/strict');
const fs = require('node:fs');
const {execFileSync} = require('node:child_process');
const {chromium} = require(process.env.QR_PLAYWRIGHT || 'playwright');

(async () => {
    const fixture = execFileSync('php', ['tests/qr-editor-fixture.php'], {encoding: 'utf8'});
    const card = fixture.match(/<article class="signature-card"[\s\S]*?<\/article>/)[0];
    const styles = fs.readFileSync('resources/views/restaurant/tables/card-style.blade.php', 'utf8')
        .match(/<style>[\s\S]*?<\/style>/)[0];
    const appStyles = fs.readFileSync('public/assets/app.css', 'utf8');
    const browser = await chromium.launch({
        ...(process.platform === 'win32' ? {channel: 'msedge'} : {}), headless: true,
    });
    const mm = value => value * 96 / 25.4;
    const close = (actual, expected, message) => assert(Math.abs(actual - expected) < .2,
        `${message}: expected ${expected}, got ${actual}`);
    try {
        const page = await browser.newPage();
        // Include the real app stylesheet: the old standalone mockup missed the
        // responsive image rule that squeezed portrait QRs in the editor.
        for (const appCss of [false, true]) {
            await page.setContent((appCss ? `<style>${appStyles}</style>` : '') + styles + card);
            for (const orientation of ['portrait', 'landscape']) {
                await page.locator('.signature-card').evaluate((el, value) =>
                    el.classList.toggle('is-landscape', value === 'landscape'), orientation);
                for (const size of [38, 46, 50]) {
                    await page.locator('.signature-card').evaluate((el, value) =>
                        el.style.setProperty('--qr-size', `${value}mm`), size);
                    for (const media of ['screen', 'print']) {
                        await page.emulateMedia({media});
                        const label = `${orientation}, ${size}mm, ${media}, app CSS=${appCss}`;
                        const image = await page.locator('.signature-frame img').boundingBox();
                        const frame = await page.locator('.signature-frame').boundingBox();
                        close(image.width, mm(size - 6), `${label}: QR width`);
                        close(image.height, image.width, `${label}: QR stays square`);
                        close(frame.width, mm(size - 1), `${label}: approved frame width`);
                        close(frame.height, frame.width, `${label}: no white bands`);
                        close(image.x - frame.x, mm(2.5), `${label}: horizontal padding`);
                        close(image.y - frame.y, mm(2.5), `${label}: vertical padding`);
                    }
                }
            }
        }
        console.log('QR spacing passed: square artwork and equal padding in portrait/landscape, editor/print, all QR sizes.');
    } finally {
        await browser.close();
    }
})().catch(error => {console.error(error); process.exitCode = 1;});
