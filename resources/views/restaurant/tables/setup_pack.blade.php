<!doctype html>
<html lang="en" data-zem-palette="plain">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<title>{{ $restaurant->name }} QR Setup Pack</title>
@include('restaurant.tables.card-style')
<style>
body{margin:0;padding:24px;background:#e9e9e7;color:#171717;font-family:Arial,Helvetica,sans-serif}
.pack-toolbar{max-width:330mm;margin:0 auto 24px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
.pack-toolbar h1{font-size:24px;margin:0 0 8px}.pack-toolbar p{font-size:14px;margin:0;max-width:720px}
.pack-toolbar button{background:#171717;color:white;padding:14px 20px;border:0;border-radius:8px;cursor:pointer}
.pack-scroll{overflow:auto}.qr-page{page:qr-portrait;box-sizing:border-box;display:grid;grid-template-columns:repeat(4,75mm);grid-template-rows:repeat(2,140mm);justify-content:center;align-content:center;gap:0;width:330mm;height:350mm;margin:0 auto 24px;background:white;box-shadow:0 8px 40px #0002}.qr-page[data-card-size="medium"]:not(.is-landscape){grid-template-columns:repeat(5,62.5mm);grid-template-rows:repeat(2,116.667mm)}.qr-page[data-card-size="small"]:not(.is-landscape){grid-template-columns:repeat(6,50mm);grid-template-rows:repeat(3,93.333mm)}.qr-page.is-landscape{page:qr-landscape;grid-template-columns:182mm;grid-template-rows:repeat(3,97.5mm);justify-content:center;align-content:center;justify-items:center;align-items:center;width:350mm;height:330mm}.qr-page.is-landscape[data-card-size="medium"]{grid-template-columns:repeat(3,116.667mm);grid-template-rows:repeat(5,62.5mm)}.qr-page.is-landscape[data-card-size="small"]{grid-template-columns:repeat(3,93.333mm);grid-template-rows:repeat(6,50mm)}.qr-page.is-landscape .signature-card{transform:scale(1.3);transform-origin:center center}.qr-page.is-landscape[data-card-size="medium"] .signature-card,.qr-page.is-landscape[data-card-size="small"] .signature-card{transform:none}
@page qr-portrait{size:330mm 350mm;margin:0}
@page qr-landscape{size:350mm 330mm;margin:0}
@media print{html,body{width:auto;height:auto;margin:0!important;padding:0!important;background:white}.no-print{display:none!important}.pack-scroll{overflow:visible}.qr-page{margin:0;box-shadow:none;break-after:page;page-break-after:always}.qr-page:last-child{break-after:auto;page-break-after:auto}}
</style>
</head>
<body>
<header class="pack-toolbar no-print"><div><h1>{{ $restaurant->name }} · Signature QR pack</h1><p>Table/lobby and room cards each use their own saved editor orientation and size. Large keeps the current size; Medium is 62.5 × 116.7 mm and Small is 50 × 93.3 mm, rotated for landscape layouts. Landscape Large retains the current 30% print enlargement.</p><p>Portrait sheets are 330 × 350 mm and landscape sheets are 350 × 330 mm. Smaller cards are efficiently tiled with more cards per sheet.</p><p>The PDF keeps QR codes, text, and artwork as vectors instead of flattening the page to a screenshot. Logo images are embedded directly; use the original high-resolution logo (SVG where available) for the sharpest print.</p><p id="pack-status" aria-live="polite">Preparing your QR pages…</p></div><button type="button" onclick="printSetupPack()" id="print-pack-button" disabled>Preparing pack…</button></header>
<main class="pack-scroll" id="qr-pack" aria-busy="true"></main>
<script>
const setupPackBatchUrls=@json($batchUrls);
const setupPackPublishUrl=@json(route('restaurant.tables.setup-pack.publish'));
const setupPackCsrf=@json(csrf_token());
const setupPackBuildToken=@json($buildToken);
const setupPackPageCount={{ $pageCount }};
const pack=document.getElementById('qr-pack');
const packStatus=document.getElementById('pack-status');
const printButton=document.getElementById('print-pack-button');
let allPagesReady=false;

async function loadSetupPack(){
 if(setupPackPageCount===0){pack.innerHTML='<p>No active tables or rooms are available for this setup pack.</p>';packStatus.textContent='No active tables or rooms found.';printButton.disabled=true;return;}
 for(let page=0;page<setupPackPageCount;page++){
  packStatus.textContent=`Preparing saved-orientation page ${page+1} of ${setupPackPageCount}…`;
  const response=await fetch(setupPackBatchUrls[page],{headers:{'X-Requested-With':'XMLHttpRequest'}});
  if(!response.ok)throw new Error(`Batch ${page+1} failed (${response.status})`);
  pack.insertAdjacentHTML('beforeend',await response.text());
 }
 allPagesReady=true;
 pack.setAttribute('aria-busy','false');
 packStatus.textContent=`${setupPackPageCount} custom-size page${setupPackPageCount===1?'':'s'} ready. Saving a reusable print file…`;
 printButton.disabled=false;
 printButton.textContent='Print setup pack';
 window.fitSignatureTitles();
 const shell=document.documentElement.cloneNode(true);
 const shellPack=shell.querySelector('#qr-pack');
 shellPack.innerHTML='<!--QR_PACK_PAGES-->';
 shellPack.setAttribute('aria-busy','false');
 const shellStatus=shell.querySelector('#pack-status');
 if(shellStatus)shellStatus.textContent='All QR pages are ready. Save as PDF at 100% scale; each card group follows its independently saved orientation and size.';
 const publish=await fetch(setupPackPublishUrl,{method:'POST',credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':setupPackCsrf},body:JSON.stringify({build:setupPackBuildToken,page_count:setupPackPageCount,shell:'<!doctype html>'+shell.outerHTML})});
 if(!publish.ok)throw new Error(`Could not save reusable print file (${publish.status})`);
 const result=await publish.json();
 packStatus.textContent='Reusable print file ready with the saved card orientations and sizes.';
 window.location.replace(result.url);
}

async function printSetupPack(){
 const resources=Array.from(document.querySelectorAll('[data-print-resource]'));
 await Promise.all(resources.map(resource=>resource.complete ? Promise.resolve() : new Promise(resolve=>{resource.addEventListener('load',resolve,{once:true});resource.addEventListener('error',resolve,{once:true});})));
 if(resources.some(resource=>resource.naturalWidth===0)){alert('A logo or QR image could not load. Reload before printing.');return;}
 window.fitSignatureTitles();window.print();
}
loadSetupPack().catch(error=>{console.error(error);packStatus.textContent=allPagesReady?'The prepared pages are ready, but the reusable print file could not be saved. You can still print this pack.':'The QR pack could not finish loading. Reload and try again.';printButton.textContent=allPagesReady?'Print prepared pages':'Retry setup pack';printButton.disabled=false;printButton.onclick=allPagesReady?()=>printSetupPack():()=>window.location.reload();});
</script>
</body></html>
