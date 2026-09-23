<!doctype html>
<html lang="en" data-zem-palette="plain">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<title>{{ $restaurant->name }} QR Setup Pack</title>
@include('restaurant.tables.card-style')
<style>
body{margin:0;padding:24px;background:#e9e9e7;color:#171717;font-family:Arial,Helvetica,sans-serif}
.pack-toolbar{max-width:1123px;margin:0 auto 24px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
.pack-toolbar h1{font-size:24px;margin:0 0 8px}.pack-toolbar p{font-size:14px;margin:0;max-width:720px}
.pack-toolbar button{background:#171717;color:white;padding:14px 20px;border:0;border-radius:8px;cursor:pointer}
.pack-scroll{overflow:auto}.qr-page{display:grid;grid-template-columns:repeat(4,74.25mm);grid-template-rows:repeat(3,140mm);width:297mm;height:420mm;margin:0 auto 24px;background:white;box-shadow:0 8px 40px #0002}
@page{size:A3 portrait;margin:0}
@media print{html,body{width:297mm;margin:0!important;padding:0!important;background:white}.no-print{display:none!important}.pack-scroll{overflow:visible}.qr-page{margin:0;box-shadow:none;break-after:page;page-break-after:always}.qr-page:last-child{break-after:auto;page-break-after:auto}}
</style>
</head>
<body>
<header class="pack-toolbar no-print"><div><h1>{{ $restaurant->name }} · Signature QR pack</h1><p>12 cards · 4 across × 3 down. Print A3 portrait, 100% scale, no margins, background graphics enabled. Borderless printing is needed for edge-to-edge artwork.</p><p id="pack-status" aria-live="polite">Preparing your QR pages…</p></div><button type="button" onclick="printSetupPack()" id="print-pack-button" disabled>Preparing pack…</button></header>
<main class="pack-scroll" id="qr-pack" aria-busy="true"></main>
<script>
const setupPackBatchUrl=@json(route('restaurant.tables.setup-pack.batch'));
const setupPackPageCount=Math.ceil({{ $tableCount }}/{{ $batchSize }});
const pack=document.getElementById('qr-pack');
const packStatus=document.getElementById('pack-status');
const printButton=document.getElementById('print-pack-button');

async function loadSetupPack(){
 if(setupPackPageCount===0){pack.innerHTML='<p>No active tables or rooms are available for this setup pack.</p>';packStatus.textContent='No active tables or rooms found.';printButton.disabled=true;return;}
 for(let page=0;page<setupPackPageCount;page++){
  packStatus.textContent=`Preparing A3 page ${page+1} of ${setupPackPageCount}…`;
  const response=await fetch(`${setupPackBatchUrl}?page=${page}`,{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}});
  if(!response.ok)throw new Error(`Batch ${page+1} failed (${response.status})`);
  pack.insertAdjacentHTML('beforeend',await response.text());
 }
 pack.setAttribute('aria-busy','false');
 packStatus.textContent=`${setupPackPageCount} A3 page${setupPackPageCount===1?'':'s'} ready. Your computer will print them together.`;
 printButton.disabled=false;
 printButton.textContent='Print setup pack';
}

async function printSetupPack(){
 const resources=Array.from(document.querySelectorAll('[data-print-resource]'));
 const resources=Array.from(document.querySelectorAll('[data-print-resource]'));
 await Promise.all(resources.map(resource=>resource.complete ? Promise.resolve() : new Promise(resolve=>{resource.addEventListener('load',resolve,{once:true});resource.addEventListener('error',resolve,{once:true});})));
 if(resources.some(resource=>resource.naturalWidth===0)){alert('A logo or QR image could not load. Reload before printing.');return;}
 window.fitSignatureTitles();window.print();
}
loadSetupPack().catch(error=>{console.error(error);packStatus.textContent='The QR pack could not finish loading. Reload and try again.';printButton.textContent='Retry setup pack';printButton.disabled=false;printButton.onclick=()=>window.location.reload();});
</script>
</body></html>
