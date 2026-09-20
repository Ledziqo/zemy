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
<header class="pack-toolbar no-print"><div><h1>{{ $restaurant->name }} · Signature QR pack</h1><p>12 cards · 4 across × 3 down. Print A3 portrait, 100% scale, no margins, background graphics enabled. Borderless printing is needed for edge-to-edge artwork.</p></div><button type="button" onclick="printSetupPack()">Print setup pack</button></header>
<main class="pack-scroll">
@forelse($tables->chunk(12) as $pageTables)
<section class="qr-page">
@foreach($pageTables as $table)
@include('restaurant.tables.card', ['qrImage'=>$qrImages[$table->id], 'scanText'=>$table->isRoomServicePoint() ? $sticker['room_scan_text'] : $sticker['table_scan_text']])
@endforeach
</section>
@empty
<p>No active tables or rooms are available for this setup pack.</p>
@endforelse
</main>
<script>
async function printSetupPack(){
 const resources=Array.from(document.querySelectorAll('[data-print-resource]'));
 await Promise.all(resources.map(resource=>resource.complete ? Promise.resolve() : new Promise(resolve=>{resource.addEventListener('load',resolve,{once:true});resource.addEventListener('error',resolve,{once:true});})));
 if(resources.some(resource=>resource.naturalWidth===0)){alert('A logo or QR image could not load. Reload before printing.');return;}
 window.fitSignatureTitles();window.print();
}
</script>
</body></html>
