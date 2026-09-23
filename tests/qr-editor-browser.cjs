const {chromium}=require(process.env.QR_PLAYWRIGHT);
const fs=require('fs'),{execFileSync}=require('child_process'),assert=require('assert');
(async()=>{
 const browser=await chromium.launch({channel:'msedge',headless:true});
 const page=await browser.newPage({viewport:{width:1400,height:1500}});
 const errors=[];page.on('pageerror',e=>errors.push(e.message));
 const js=fs.readFileSync('public/assets/qr-editor.js','utf8');
 let html=execFileSync('php',['tests/qr-editor-fixture.php'],{encoding:'utf8'});
 html=html.replace(/<script src="[^"]*qr-editor.js[^"]*"><\/script>/,'<script>'+js+'</script>').replace('<details class="qr-studio">','<details class="qr-studio" open>');
 await page.setContent(html);
 const keys=await page.locator('#qr-layer option').evaluateAll(es=>es.map(e=>e.value));
 for(const key of keys){
   await page.selectOption('#qr-layer',key);
   const layer=page.locator('[data-layer="'+key+'"]');
   const before=await layer.boundingBox();
   assert(before,key+' present');
   const b=await page.locator('.qr-selection').boundingBox();
   await page.mouse.move(b.x+b.width/2,b.y+b.height/2);
   await page.mouse.down();await page.mouse.move(b.x+b.width/2+12,b.y+b.height/2+8,{steps:4});await page.mouse.up();
   const after=await layer.boundingBox();
   assert(Math.abs(after.x-before.x-12)<2,key+' moves');
   await page.locator('#qr-layer-width').fill('135');
   await page.locator('#qr-layer-height').fill('125');
   const sized=await layer.boundingBox();
   assert(Math.abs(sized.width/after.width-1.35)<.03,key+' width resizes');
   assert(Math.abs(sized.height/after.height-1.25)<.03,key+' height resizes');
   assert(Number(await page.locator('input[name="elements['+key+'][sx]"]').inputValue())===1.35,key+' serialized');
   await page.locator('#qr-layer-reset').click();
 }
 await page.selectOption('#qr-layer','title');
 const handle=await page.locator('.qr-selection [data-axis="xy"]').boundingBox();
 await page.mouse.move(handle.x+6,handle.y+6);await page.mouse.down();await page.mouse.move(handle.x+22,handle.y+18);await page.mouse.up();
 assert(Number(await page.locator('input[name="elements[title][sx]"]').inputValue())>1,'corner resize');
 const saved=await page.locator('input[name^="elements["]').evaluateAll(inputs=>{
   const result={};
   inputs.forEach(input=>{const [,key,d]=input.name.match(/elements\[([^\]]+)\]\[([^\]]+)\]/);(result[key]??={})[d]=Number(input.value)});
   return result;
 });
 const restored=execFileSync('php',['tests/qr-editor-fixture.php',JSON.stringify(saved)],{encoding:'utf8'});
 assert(restored.includes('scale:'+saved.title.sx+' '+saved.title.sy),'saved scale rendered by shared print card');
 await page.setContent(restored.replace(/<script src="[^"]*qr-editor.js[^"]*"><\/script>/,'<script>'+js+'</script>').replace('<details class="qr-studio">','<details class="qr-studio" open>'));
 assert.equal(await page.locator('[data-layer="title"]').evaluate(el=>el.style.scale),saved.title.sx+' '+saved.title.sy,'reload keeps size');
 assert.deepEqual(errors,[]);
 console.log('PASS: all '+keys.length+' layers drag, independently resize, and serialize; corner resize; no browser errors');
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
