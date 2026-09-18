<!doctype html>
<html lang="en" data-zem-palette="plain">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $restaurant->name }} QR Setup Pack</title>
    @include('components.frontend-assets')
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
            main { display: grid !important; grid-template-columns: repeat(2, 1fr) !important; grid-auto-rows: 59.4mm !important; gap: 0 !important; max-width: none !important; padding: 0 !important; }
            article { break-inside: avoid; page-break-inside: avoid; box-shadow: none !important; border-radius: 0 !important; padding: 8px !important; min-height: 59.4mm !important; }
            @page { size: A3 portrait; margin: 0; }
        }
        @media screen {
            article { min-height: 350px; }
        }
    </style>
</head>
<body class="bg-neutral-100 p-5 text-neutral-950">
@php($logoUrl = $restaurant->logo_path ? (\Illuminate\Support\Str::startsWith($restaurant->logo_path, ['http://', 'https://', 'uploads/']) ? (str_starts_with($restaurant->logo_path, 'uploads/') ? asset($restaurant->logo_path) : $restaurant->logo_path) : asset('storage/'.$restaurant->logo_path)) : null)
@php($sticker = $sticker ?? array_merge(['background_color' => '#FFFFFF', 'border_color' => '#111111', 'text_color' => '#111111', 'accent_color' => $restaurant->primary_color ?: '#D22630', 'qr_color' => '#111111', 'qr_background_color' => '#FFFFFF', 'design' => 'classic', 'table_scan_text' => 'SCAN TO ORDER', 'room_scan_text' => 'SCAN FOR ROOM SERVICE'], $restaurant->settings['qr_sticker'] ?? []))
@php($cardClass = match($sticker['design']) { 'rounded' => 'rounded-lg border-2', 'bold' => 'rounded-sm border-4', default => 'rounded-none border' })
<div class="no-print mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-black text-black">{{ $restaurant->name }} QR setup pack</h1>
        <p class="text-sm font-semibold text-neutral-800">Print this page — 4 QR cards per A3 page.</p>
    </div>
    <button type="button" onclick="printSetupPack()" class="rounded-lg bg-black px-5 py-3 font-bold text-white">Print setup pack</button>
</div>

<main class="grid gap-4 sm:grid-cols-2 max-w-4xl mx-auto">
    @forelse($tables as $table)
        @php($menuUrl = route('menu.show', [$restaurant->slug, $table->table_number]))
        <article class="qr-card flex flex-col items-center {{ $cardClass }} p-2 text-center" style="background-color: {{ $sticker['background_color'] }}; border-color: {{ $sticker['border_color'] }}; color: {{ $sticker['text_color'] }};">
            <div class="flex items-center justify-center w-full">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $restaurant->name }} logo" class="h-12 w-12 object-contain">
                @endif
            </div>
            <p class="scan-label mt-1 text-[9px] font-black uppercase tracking-[.12em]" style="color: {{ $sticker['accent_color'] }}">{{ $table->isRoomServicePoint() ? $sticker['room_scan_text'] : $sticker['table_scan_text'] }}</p>
            <div class="mt-1 grid place-items-center">
                <img src="{{ $qrImages[$table->id] }}" alt="QR code for {{ $table->locationTypeLabel() }} {{ $table->table_number }}" class="h-28 w-28 contrast-125" data-print-resource width="112" height="112">
            </div>
            <p class="mt-1 text-xs font-black" style="color: {{ $sticker['text_color'] }}">{{ $table->displayLabel() }}</p>
            <div class="mt-1 flex items-center justify-center gap-1 border-t pt-1 w-full" style="border-color: {{ $sticker['border_color'] }};">
                <span class="text-[8px] font-bold" style="color: {{ $sticker['text_color'] }}">Powered by</span>
                <img src="{{ asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}" alt="ZemTab" class="h-5 w-auto">
            </div>
        </article>
    @empty
        <p class="rounded-xl bg-white p-5 font-semibold text-neutral-900 col-span-2">No active tables or rooms are available for this setup pack.</p>
    @endforelse
</main>
<script>
    async function printSetupPack() {
        const resources = Array.from(document.querySelectorAll('[data-print-resource]'));

        await Promise.all(resources.map((resource) => {
            if (resource.complete) return Promise.resolve();

            return new Promise((resolve) => {
                resource.addEventListener('load', resolve, { once: true });
                resource.addEventListener('error', resolve, { once: true });
            });
        }));

        if (resources.some((resource) => resource.naturalWidth === 0)) {
            alert('Some QR codes could not load. Reload the setup pack before printing.');
            return;
        }

        window.print();
    }
</script>
</body>
</html>
