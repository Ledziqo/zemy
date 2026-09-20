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
            min-height: 0;
            aspect-ratio: 74.25 / 140;
            padding: 9% 8% 7%;
            border: 1px solid var(--qr-border);
            font-family: Arial, Helvetica, sans-serif;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
            position: relative;
            isolation: isolate;
            overflow: hidden;
        }

        .qr-card > * { position: relative; z-index: 1; }
        .qr-card .qr-art { position: absolute; inset: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; }
        .qr-logo { background: white; border-radius: 12px; padding: 8px; }
        .qr-heading { width: 100%; text-align: left; }
        .qr-eyebrow { font-size: 8px; font-weight: 700; letter-spacing: .24em; margin-bottom: 9px; }
        .scan-label { font-size: clamp(15px, 2.1vw, 27px); font-weight: 900; line-height: 1.04; letter-spacing: -.045em; max-width: 100%; overflow-wrap: anywhere; margin: 0; }
        .qr-frame { padding: 9px; border: 2px solid var(--qr-accent); border-radius: 18px; background: white; width: 100%; max-width: 52mm; }
        .qr-frame .qr-image { display: block; width: 100%; height: auto; }
        .qr-hint { font-size: 9px; letter-spacing: .04em; margin: 8px 0 0; }
        .qr-footer { background: white; color: #171717; border-radius: 30px; padding: 7px 12px; width: auto; }
        @media screen and (max-width: 760px) { .qr-page { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media screen and (max-width: 420px) { .qr-page { grid-template-columns: 1fr; } }

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
            main { display: block !important; }
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
                gap: 2mm;
            }
            .qr-logo { width: 48mm !important; height: 26mm !important; flex-shrink: 0; }
            .qr-frame { width: 52mm; padding: 2mm; flex-shrink: 0; }
            .qr-frame .qr-image { width: 47mm !important; height: 47mm !important; margin: 0 !important; }
            .scan-label { font-size: 18pt !important; }
            .qr-eyebrow { font-size: 6pt; margin-bottom: 2mm; }
            .qr-hint { font-size: 6pt; }
            .qr-footer { padding: 1.5mm 3mm !important; }
        }
    </style>
</head>
<body class="bg-neutral-100 p-5 text-neutral-950">
@php($logoUrl = $restaurant->logo_path ? (\Illuminate\Support\Str::startsWith($restaurant->logo_path, ['http://', 'https://', 'uploads/']) ? (str_starts_with($restaurant->logo_path, 'uploads/') ? asset($restaurant->logo_path) : $restaurant->logo_path) : asset('storage/'.$restaurant->logo_path)) : null)
@php($sticker = $sticker ?? array_merge(['background_color' => '#FFFFFF', 'border_color' => '#111111', 'text_color' => '#111111', 'accent_color' => $restaurant->primary_color ?: '#D22630', 'qr_color' => '#111111', 'qr_background_color' => '#FFFFFF', 'design' => 'classic', 'table_scan_text' => 'SCAN TO ORDER', 'room_scan_text' => 'SCAN FOR ROOM SERVICE'], $restaurant->settings['qr_sticker'] ?? []))
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
                    <article class="qr-card flex flex-col items-center justify-between text-center" style="--qr-accent: {{ $sticker['accent_color'] }}; --qr-border: {{ $sticker['border_color'] }}; background-color: {{ $sticker['background_color'] }}; border-color: {{ $sticker['border_color'] }}; color: {{ $sticker['text_color'] }};">
                        <svg class="qr-art" viewBox="0 0 297 560" preserveAspectRatio="none" aria-hidden="true">
                            <path d="M174 0H297V176C242 164 223 120 243 81C267 34 224 18 174 0Z" fill="var(--qr-accent)"/>
                            <path d="M210 -20C159 60 314 78 282 170M231 -20C180 60 335 78 303 170M252 -20C201 60 356 78 324 170" fill="none" stroke="var(--qr-border)" stroke-width="1.5" opacity=".45"/>
                            <path d="M0 416C56 411 31 470 102 492C180 516 183 534 297 521V560H0Z" fill="var(--qr-accent)"/>
                            <path d="M-25 457C63 440 37 507 128 516S220 568 330 541M-25 469C63 452 37 519 128 528S220 580 330 553M-25 481C63 464 37 531 128 540S220 592 330 565" fill="none" stroke="var(--qr-border)" stroke-width="1.5" opacity=".55"/>
                            <circle cx="20" cy="210" r="5" fill="var(--qr-accent)"/><path d="M267 236h12m-6-6v12" stroke="var(--qr-accent)" stroke-width="2"/>
                        </svg>
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $restaurant->name }} logo" class="qr-logo" data-print-resource>
                        @endif
                        <div class="qr-heading"><div class="qr-eyebrow">YOUR MENU. ONE SCAN AWAY.</div><p class="scan-label">{{ $table->isRoomServicePoint() ? $sticker['room_scan_text'] : $sticker['table_scan_text'] }}</p></div>
                        <div><div class="qr-frame"><img src="{{ $qrImages[$table->id] }}" alt="QR code for {{ $table->locationTypeLabel() }} {{ $table->table_number }}" class="qr-image" data-print-resource width="500" height="500"></div><p class="qr-hint">Open your camera · Scan · Enjoy</p></div>
                        <div class="qr-footer flex items-center justify-center gap-1">
                            <span class="text-[8px] font-bold">Powered by</span>
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
