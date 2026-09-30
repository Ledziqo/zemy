const assert = require('node:assert/strict');
const fs = require('node:fs');
const {execFileSync} = require('node:child_process');
const {chromium} = require(process.env.QR_PLAYWRIGHT || 'playwright');

(async () => {
    // Render the real card without needing a database or hosted application.
    const fixture = execFileSync('php', ['tests/qr-editor-fixture.php'], {encoding: 'utf8'});
    const card = fixture.match(/<article class="signature-card"[\s\S]*?<\/article>/)[0];
    const styles = ['card-style', 'setup_pack'].map(name =>
        fs.readFileSync(`resources/views/restaurant/tables/${name}.blade.php`, 'utf8')
            .match(/<style>[\s\S]*?<\/style>/)[0]
    ).join('');
    const browser = await chromium.launch({
        ...(process.platform === 'win32' ? {channel: 'msedge'} : {}), headless: true,
    });
    try {
        const page = await browser.newPage();
        await page.setContent(styles + '<main class="pack-scroll"><section class="qr-page">' + card + '</section></main>');
        await page.locator('.signature-footer img').evaluate(el => el.remove());
        // The QR now intentionally embeds a raster brand icon. Isolate the venue
        // logo when asserting that a vector logo is not rasterized by printing.
        await page.locator('.signature-frame img').evaluate(el => el.remove());
        const logo = page.locator('.signature-logo');
        const setLogo = async source => {
            await logo.evaluate((el, src) => {el.src = src;}, source);
            await page.evaluate(() => Promise.all([...document.images].map(img => img.decode())));
        };
        const svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1000" height="400" viewBox="0 0 1000 400"><path d="M10 10H990V390H10Z" fill="none" stroke="black"/><text x="60" y="240" font-size="110">Print quality</text></svg>';
        await setLogo('data:image/svg+xml;base64,' + Buffer.from(svg).toString('base64'));
        const vectorPdf = await page.pdf({preferCSSPageSize: true, printBackground: true});
        assert(!/\/Subtype\s*\/Image\b/.test(vectorPdf.toString('latin1')),
            'SVG logos must remain vector artwork in the exported PDF');

        const png = fs.readFileSync('public/logo/zemtab-pantone-1795-c-icon-text-transparent.png');
        const width = png.readUInt32BE(16), height = png.readUInt32BE(20);
        await setLogo('data:image/png;base64,' + png.toString('base64'));
        // Saved editor transforms must not flatten the image either.
        await logo.evaluate(el => {el.style.scale = '1.5 1.25';});
        const rasterPdf = await page.pdf({preferCSSPageSize: true, printBackground: true});
        const imageDictionaries = rasterPdf.toString('latin1').match(/<<[^]*?\/Subtype\s*\/Image\b[^]*?>>/g) || [];
        assert(imageDictionaries.some(dictionary =>
            new RegExp(`/Width\\s+${width}\\b`).test(dictionary) &&
            new RegExp(`/Height\\s+${height}\\b`).test(dictionary)),
            'Raster logos must retain their source pixel dimensions in the PDF');

        await page.setContent(styles + '<main><section class="qr-page"><article class="signature-card"></article></section><section class="qr-page is-landscape"><article class="signature-card is-landscape"></article></section></main>');
        const orientationPdf = (await page.pdf({preferCSSPageSize: true, printBackground: true})).toString('latin1');
        const pageBoxes = [...orientationPdf.matchAll(/\/MediaBox\s*\[\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*\]/g)]
            .map(([, , , right, top]) => [Number(right), Number(top)]);
        assert(pageBoxes.length >= 2, 'PDF must contain portrait and landscape pages');
        const pointsForMm = mm => mm * 72 / 25.4;
        const closeTo = (actual, expected) => Math.abs(actual - expected) < 1;
        assert(pageBoxes.some(([width, height]) => closeTo(width, pointsForMm(330)) && closeTo(height, pointsForMm(350))),
            'portrait design must print on a 330 × 350 mm PDF page');
        assert(pageBoxes.some(([width, height]) => closeTo(width, pointsForMm(350)) && closeTo(height, pointsForMm(330))),
            'landscape design must print on a 350 × 330 mm PDF page');
        console.log(`PDF checks passed: SVG stays vector; transformed PNG retains ${width} × ${height} source pixels.`);
    } finally {
        await browser.close();
    }
})().catch(error => {console.error(error);process.exitCode = 1;});
