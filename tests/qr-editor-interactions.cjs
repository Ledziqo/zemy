const assert=require('node:assert/strict');
const fs=require('node:fs'),{execFileSync}=require('node:child_process');
const {chromium}=require(process.env.QR_PLAYWRIGHT||'playwright');
(async()=>{
 const editor=fs.readFileSync('public/assets/qr-editor.js','utf8');
 const fixture=execFileSync('php',['tests/qr-editor-fixture.php'],{encoding:'utf8'});
 const html=fixture.replace(/<script src="[^"]*qr-editor.js[^"]*"><\/script>/,()=>'<script>'+editor+'</script>').replace('<details class="qr-studio">','<details class="qr-studio" open>');
 const browser=await chromium.launch({...(process.platform==='win32'?{channel:'msedge'}:{}),headless:true});
 try{
  const page=await browser.newPage({viewport:{width:1450,height:1800}}),errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  await page.setContent('<style>'+fs.readFileSync('public/assets/app.css','utf8')+'</style>'+html);
  const undo=page.locator('#qr-layer-undo'),angle=page.locator('#qr-layer-rotation');
  const value=async(name)=>Number(await page.locator(`input[name="elements[frame][${name}]"]`).inputValue());
  await page.selectOption('#qr-layer','frame');
  await angle.fill('30');
  assert.equal(await page.locator('[data-layer="frame"]').evaluate(el=>el.style.rotate),'30deg');
  await undo.click();assert.equal(await value('r'),0,'Undo restores rotation');
  const before=[await value('sx'),await value('sy')];
  const corner=await page.locator('.qr-selection [data-axis="xy"]').boundingBox();
  await page.mouse.move(corner.x+6,corner.y+6);await page.mouse.down();
  await page.mouse.move(corner.x+40,corner.y+18,{steps:5});await page.mouse.up();
  assert(await value('sx')>before[0],'corner drag grows the QR');
  assert(Math.abs((await value('sx'))/before[0]-(await value('sy'))/before[1])<.001,'corner keeps proportions with uneven pointer movement');
  await undo.click();assert.equal(await value('sx'),before[0]);assert.equal(await value('sy'),before[1]);
  const rotate=await page.locator('.qr-selection [data-rotate]').boundingBox();
  const rect=await page.locator('[data-layer="frame"]').boundingBox();
  await page.mouse.move(rotate.x+9,rotate.y+9);await page.mouse.down();
  await page.mouse.move(rect.x+rect.width+30,rect.y+rect.height/2,{steps:8});await page.mouse.up();
  assert(Math.abs(await value('r'))>40,'rotation handle turns the element');
  await undo.click();assert.equal(await value('r'),0);
  await angle.fill('45');
  await page.locator('input[name="orientation"][value="landscape"]').check();
  assert.equal(await value('r'),0,'old layouts without rotation do not inherit it');
  await page.locator('input[name="orientation"][value="portrait"]').check();
  assert.equal(await value('r'),45,'portrait restores its rotation');
  await page.locator('#qr-design-reset').click();assert.equal(await value('r'),0);
  await undo.click();assert.equal(await value('r'),45,'Undo restores a whole layout reset');
  const saved=await page.locator('input[name^="elements["]').evaluateAll(inputs=>{
   const state={};for(const input of inputs){const [,key,d]=input.name.match(/elements\[([^\]]+)\]\[([^\]]+)\]/);(state[key]??={})[d]=Number(input.value)}return state;
  });
  const printed=execFileSync('php',['tests/qr-editor-fixture.php',JSON.stringify(saved)],{encoding:'utf8'});
  assert(printed.includes('rotate:45deg;'),'saved rotation is rendered by the print card');
  assert.deepEqual(errors,[]);
  console.log('Editor interactions passed: rotation control/handle, proportional corner drag, Undo, layout isolation, reset and saved rotation.');
 }finally{await browser.close()}
})().catch(e=>{console.error(e);process.exitCode=1});
