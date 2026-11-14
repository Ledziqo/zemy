<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>{{ $restaurant->name }} menu QR</title>
@include('restaurant.tables.card-style')
<style>
@page { size: A4; margin: 15mm; }
body { margin: 0; font-family: Arial, sans-serif; }
.print-actions { margin: 20px; }
.print-card{break-inside:avoid;page-break-inside:avoid;margin-bottom:10mm;}
.print-actions button { padding: 12px 20px; cursor: pointer; }
@media print { .print-actions { display: none; } * { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }
</style></head><body>
<div class="print-actions"><button onclick="window.print()">Print / Save PDF</button><p>Print at 100% scale with background graphics enabled.</p></div>
@foreach($printCards as $printCard)
<div class="print-card">@include('restaurant.tables.card', ['sticker' => $printCard['sticker'], 'qrImage' => $printCard['qrImage'], 'scanText' => $printCard['sticker']['scan_text'], 'locationLabel' => null])</div>
@endforeach
<script>
window.addEventListener('load',async()=>{
 await Promise.all([...document.images].map(img=>img.decode?.().catch(()=>{})));
 if(document.fonts)await document.fonts.ready;
 window.fitSignatureTitles?.();
 requestAnimationFrame(()=>requestAnimationFrame(()=>window.print()));
});
</script>
</body></html>
