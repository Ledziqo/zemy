<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>{{ $restaurant->name }} menu QR</title>
@include('restaurant.tables.card-style')
<style>
@page { size: A4; margin: 15mm; }
body { margin: 0; font-family: Arial, sans-serif; }
.print-actions { margin: 20px; }
.print-actions button { padding: 12px 20px; cursor: pointer; }
@media print { .print-actions { display: none; } * { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }
</style></head><body>
<div class="print-actions"><button onclick="window.print()">Print / Save PDF</button><p>Print at 100% scale with background graphics enabled.</p></div>
@include('restaurant.tables.card', ['scanText' => $sticker['scan_text'], 'locationLabel' => null])
</body></html>
