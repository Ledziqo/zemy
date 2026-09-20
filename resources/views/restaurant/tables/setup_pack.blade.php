<!doctype html>
<html lang="en" data-zem-palette="plain">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $restaurant->name }} QR Setup Pack</title>
    @include('components.frontend-assets')
    <style>
        .qr-page {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .qr-card {
            min-height: 420px;
            aspect-ratio: 1 / 1.8;
            position: relative;
            isolation: isolate;
            overflow: hidden;
        }

        .qr-card > * { position: relative; z-index: 1; }

        .qr-card.design-abstract::before,
        .qr-card.design-abstract::after {
            content: "";
            position: absolute;
            z-index: 0;
            pointer-events: none;
            opacity: .16;
        }

        .qr-card.design-abstract::before {
            width: 70mm;
            height: 70mm;
            top: -24mm;
            right: -25mm;
            background: var(--qr-accent);
            border-radius: 48% 52% 63% 37% / 42% 38% 62% 58%;
            transform: rotate(22deg);
        }

        .qr-card.design-abstract::after {
            width: 55mm;
            height: 55mm;
            bottom: -23mm;
            left: -22mm;
            background: var(--qr-border);
            border-radius: 60% 40% 30% 70% / 50% 45% 55% 50%;
            transform: rotate(-18deg);
        }

        .qr-logo {
            width: min(100%, 48mm);
            height: 34mm;
            object-fit: contain;
        }

        .qr-image {
            width: min(100%, 48mm);
            height: auto;
            aspect-ratio: 1;
        }

        @media print {
            .no-print { display: none !important; }
            @page { size: A3 portrait; margin: 0; }
            html, body { width: 297mm; min-height: 420mm; }
            body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
            main { max-width: none !important; margin: 0 !important; padding: 0 !important; }
            .qr-page {
                display: grid !important;
                grid-template-columns: repeat(4, 74.25mm) !important;
                grid-template-rows: repeat(3, 140mm) !important;
                gap: 0 !important;
                width: 297mm !important;
                height: 420mm !important;
                break-after: page;
                page-break-after: always;
            }
            .qr-page:last-child { break-after: auto; page-break-after: auto; }
            .qr-card {
                box-sizing: border-box;
                width: 74.25mm;
                height: 140mm;
                min-height: 0 !important;
                padding: 7mm 5mm !important;
                break-inside: avoid;
                page-break-inside: avoid;
                box-shadow: none !important;
                border-radius: 0 !important;
                overflow: hidden;
                justify-content: space-between !important;
                gap: 4mm;
            }
            .qr-logo { width: 48mm !important; height: 34mm !important; }
            .qr-image { width: 48mm !important; height: 48mm !important; margin: 0 !important; }
            .scan-label { font-size: 7pt !important; letter-spacing: .08em; }
            .qr-footer { padding-top: 2mm !important; }
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
        <p class="text-sm font-semibold text-neutral-800">Print this page — 12 QR cards per portrait A3 page in a 4 × 3 grid. Choose A3, Portrait, 100% scale, and no margins.</p>
    </div>
    <button type="button" onclick="printSetupPack()" class="rounded-lg bg-black px-5 py-3 font-bold text-white">Print setup pack</button>
</div>

<main class="mx-auto grid max-w-6xl gap-4">
    @if($tables->isEmpty())
        <p class="rounded-xl bg-white p-5 font-semibold text-neutral-900">No active tables or rooms are available for this setup pack.</p>
    @else
        @foreach($tables->chunk(12) as $pageTables)
            <section class="qr-page">
                @foreach($pageTables as $table)
                    <article class="qr-card flex flex-col items-center justify-between {{ $cardClass }} {{ $sticker['design'] === 'abstract' ? 'design-abstract' : '' }} p-4 text-center" style="--qr-accent: {{ $sticker['accent_color'] }}; --qr-border: {{ $sticker['border_color'] }}; background-color: {{ $sticker['background_color'] }}; border-color: {{ $sticker['border_color'] }}; color: {{ $sticker['text_color'] }};">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $restaurant->name }} logo" class="qr-logo" data-print-resource>
                        @endif
                        <p class="scan-label text-[9px] font-black uppercase tracking-[.12em]" style="color: {{ $sticker['accent_color'] }}">{{ $table->isRoomServicePoint() ? $sticker['room_scan_text'] : $sticker['table_scan_text'] }}</p>
                        <img src="{{ $qrImages[$table->id] }}" alt="QR code for {{ $table->locationTypeLabel() }} {{ $table->table_number }}" class="qr-image contrast-125" data-print-resource width="500" height="500">
                        <div class="qr-footer flex w-full items-center justify-center gap-1 border-t pt-1" style="border-color: {{ $sticker['border_color'] }};">
                            <span class="text-[8px] font-bold" style="color: {{ $sticker['text_color'] }}">Powered by</span>
                            <img src="{{ asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}" alt="ZemTab" class="h-5 w-auto" data-print-resource>
                        </div>
                    </article>
                @endforeach
            </section>
        @endforeach
    @endif
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
