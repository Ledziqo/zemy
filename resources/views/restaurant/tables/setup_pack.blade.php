<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $restaurant->name }} QR Setup Pack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
            main { display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 0 !important; max-width: none !important; padding: 0 !important; }
            article { break-inside: avoid; page-break-inside: avoid; box-shadow: none !important; border-radius: 0 !important; border: 1px solid #000 !important; padding: 20px !important; min-height: 50% !important; }
            .qr-card, .qr-card * { color: #000 !important; }
            .scan-label { color: {{ $restaurant->primary_color ?: '#D22630' }} !important; }
            @page { size: A4; margin: 0; }
        }
        @media screen {
            article { min-height: 350px; }
        }
    </style>
</head>
<body class="bg-neutral-100 p-5 text-neutral-950">
@php($logoUrl = $restaurant->logo_path ? (\Illuminate\Support\Str::startsWith($restaurant->logo_path, ['http://', 'https://', 'uploads/']) ? (str_starts_with($restaurant->logo_path, 'uploads/') ? asset($restaurant->logo_path) : $restaurant->logo_path) : asset('storage/'.$restaurant->logo_path)) : null)
@php($place = $restaurant->locationLabel())
@php($placeTitle = $restaurant->locationLabelTitle())
@php($accentColor = $restaurant->primary_color ?: '#D22630')
<div class="no-print mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-black text-black">{{ $restaurant->name }} QR setup pack</h1>
        <p class="text-sm font-semibold text-neutral-800">Print this page — 4 QR cards per A4 page.</p>
    </div>
    <button onclick="window.print()" class="rounded-lg bg-black px-5 py-3 font-bold text-white">Print setup pack</button>
</div>

<main class="grid gap-4 sm:grid-cols-2 max-w-4xl mx-auto">
    @forelse($tables as $table)
        @php($menuUrl = route('menu.show', [$restaurant->slug, $table->table_number]))
        <article class="qr-card flex flex-col items-center rounded-xl border-2 border-black bg-white p-5 text-center shadow-sm">
            <div class="flex min-h-16 items-center justify-center gap-3 w-full">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $restaurant->name }} logo" class="h-16 w-16 rounded-lg border border-neutral-300 bg-white object-contain">
                @endif
                <h2 class="text-3xl font-black">{{ $restaurant->name }}</h2>
            </div>
            <p class="scan-label mt-3 text-lg font-black uppercase tracking-[.2em]" style="color: {{ $accentColor }}">{{ $restaurant->isHotel() ? 'Scan for room service' : 'Scan to order' }}</p>
            <div class="mt-3 grid place-items-center">
                <img src="{{ route('restaurant.tables.qr', $table) }}" alt="QR code for {{ $place }} {{ $table->table_number }}" class="h-48 w-48 contrast-125">
            </div>
            <p class="mt-3 text-5xl font-black">{{ $placeTitle }} {{ $table->table_number }}</p>
            @if($table->table_name)
                <p class="mt-1 text-xl font-extrabold text-neutral-900">{{ $table->table_name }}</p>
            @endif
            <p class="mt-2 break-all text-xs font-semibold text-neutral-600">{{ $menuUrl }}</p>
            <div class="mt-4 flex items-center justify-center gap-2 border-t border-neutral-200 pt-3 w-full">
                <span class="text-xs font-semibold text-neutral-500">Powered by</span>
                <img src="{{ asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}" alt="ZemTab" class="h-6 w-auto">
            </div>
        </article>
    @empty
        <p class="rounded-xl bg-white p-5 font-semibold text-neutral-900 col-span-2">No active {{ $restaurant->locationLabel(true) }} are available for this setup pack.</p>
    @endforelse
</main>
</body>
</html>
