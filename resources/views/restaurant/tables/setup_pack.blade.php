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
            body { background: #fff !important; }
            article { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-neutral-100 p-5 text-neutral-950">
@php($logoUrl = $restaurant->logo_path ? (\Illuminate\Support\Str::startsWith($restaurant->logo_path, ['http://', 'https://', 'uploads/']) ? (str_starts_with($restaurant->logo_path, 'uploads/') ? asset($restaurant->logo_path) : $restaurant->logo_path) : asset('storage/'.$restaurant->logo_path)) : null)
<div class="no-print mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-black">{{ $restaurant->name }} QR setup pack</h1>
        <p class="text-sm text-neutral-600">Print this page or save it as PDF from your browser.</p>
    </div>
    <button onclick="window.print()" class="rounded-lg bg-black px-5 py-3 font-bold text-white">Print setup pack</button>
</div>

<main class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
    @forelse($tables as $table)
        @php($menuUrl = route('menu.show', [$restaurant->slug, $table->table_number]))
        <article class="rounded-2xl border-4 border-black bg-white p-5 text-center shadow-sm">
            <div class="flex min-h-16 items-center justify-center gap-3">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $restaurant->name }} logo" class="h-14 w-14 rounded-lg object-contain">
                @endif
                <h2 class="text-2xl font-black">{{ $restaurant->name }}</h2>
            </div>
            <p class="mt-5 text-sm font-black uppercase tracking-[.25em] text-red-600">Scan to order</p>
            <div class="mt-4 grid place-items-center rounded-xl border border-neutral-200 p-4">
                <img src="{{ route('restaurant.tables.qr', $table) }}" alt="QR code for table {{ $table->table_number }}" class="h-64 w-64">
            </div>
            <p class="mt-5 text-4xl font-black">Table {{ $table->table_number }}</p>
            @if($table->table_name)
                <p class="mt-1 text-lg font-bold text-neutral-600">{{ $table->table_name }}</p>
            @endif
            <p class="mt-4 break-all text-xs text-neutral-500">{{ $menuUrl }}</p>
        </article>
    @empty
        <p class="rounded-xl bg-white p-5 text-neutral-600">No active tables are available for this setup pack.</p>
    @endforelse
</main>
</body>
</html>
