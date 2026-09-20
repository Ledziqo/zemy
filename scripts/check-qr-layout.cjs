// Run with NODE_PATH pointing to a Playwright installation.
const { chromium } = require('playwright');
const { execFileSync } = require('node:child_process');
const assert = require('node:assert/strict');
(async () => {
    const browser = await chromium.launch({headless:true, channel:'msedge'});
    const page = await browser.newPage({viewport:{width:1250,height:1850}});
    for (const variant of [[], ['--max']]) {
        const html = execFileSync('php',['scripts/check-qr-design.php',...variant],{encoding:'utf8',maxBuffer:15*1024*1024});
        await page.setContent(html,{waitUntil:'load'});
        await page.emulateMedia({media:'print'});
        await page.evaluate(() => window.fitSignatureTitles());
        const result = await page.evaluate(() => ({
            pages:document.querySelectorAll('.qr-page').length,
            cards:document.querySelectorAll('.signature-card').length,
            broken:[...document.images].filter(img=>!img.naturalWidth).length,
            overflow:[...document.querySelectorAll('.signature-card,.signature-heading')].filter(el=>el.scrollHeight>el.clientHeight+1).length,
            sheetWidth:document.querySelector('.qr-page').getBoundingClientRect().width,
            sheetHeight:document.querySelector('.qr-page').getBoundingClientRect().height,
        }));
        assert.equal(result.pages,2);assert.equal(result.cards,13);assert.equal(result.broken,0);assert.equal(result.overflow,0);
        assert.ok(Math.abs(result.sheetWidth-297*96/25.4)<1);assert.ok(Math.abs(result.sheetHeight-420*96/25.4)<1);
        if(!variant.length) await page.locator('.qr-page').first().screenshot({path:'tmp/qr-signature-preview.png'});
        console.log(variant.length?'Maximum settings':'Default settings',result);
    }
    const studio = execFileSync('php',['scripts/check-qr-design.php','--studio'],{encoding:'utf8',maxBuffer:15*1024*1024});
    await page.emulateMedia({media:'screen'});
    await page.setContent(studio,{waitUntil:'load'});
    await page.getByRole('button',{name:'Midnight & gold'}).click();
    assert.equal(await page.locator('[name=background_color]').inputValue(),'#15232d');
    await page.locator('#qr-preview-type').selectOption('room');
    assert.equal(await page.locator('.signature-title').textContent(),'SCAN FOR ROOM SERVICE');
    await page.locator('[name=room_scan_text]').fill('ROOM SERVICE, JUST A SCAN AWAY');
    assert.equal(await page.locator('.signature-title').textContent(),'ROOM SERVICE, JUST A SCAN AWAY');
    await page.getByRole('button',{name:'Reset to signature'}).click();
    assert.equal(await page.locator('[name=qr_size]').inputValue(),'46');
    assert.equal(await page.locator('.signature-title').textContent(),'SCAN FOR ROOM SERVICE');
    console.log('Studio palette, text preview, type toggle and reset passed');
    await browser.close();
})().catch(error=>{console.error(error);process.exit(1)});
