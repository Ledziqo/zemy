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
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .qr-card {
            min-height: 350px;
        }

        @media print {
            .no-print { display: none !important; }
            @page { size: A3 landscape; margin: 0; }
            html, body { width: 420mm; min-height: 297mm; }
            body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
            main { max-width: none !important; margin: 0 !important; padding: 0 !important; }
            .qr-page {
                display: grid !important;
                grid-template-columns: repeat(3, 140mm) !important;
                grid-template-rows: repeat(2, 148.5mm) !important;
                gap: 0 !important;
                width: 420mm !important;
                height: 297mm !important;
                break-after: page;
                page-break-after: always;
            }
            .qr-page:last-child { break-after: auto; page-break-after: auto; }
            .qr-card {
                box-sizing: border-box;
                width: 140mm;
                height: 148.5mm;
                min-height: 0 !important;
                padding: 8mm !important;
                break-inside: avoid;
                page-break-inside: avoid;
                box-shadow: none !important;
                border-radius: 0 !important;
                overflow: hidden;
                justify-content: flex-start !important;
                gap: 2mm;
            }
            .qr-brand-logo { width: 22mm !important; height: 18mm !important; }
            .qr-brand-name { font-size: 17pt !important; }
            .scan-label { margin-top: 3mm !important; font-size: 10pt !important; }
            .qr-image { width: 54mm !important; height: 54mm !important; margin: 2mm 0 !important; }
            .qr-type { font-size: 11pt !important; letter-spacing: .12em; }
            .qr-location { margin-top: 1mm !important; font-size: 22pt !important; line-height: 1.05 !important; }
            .qr-url { margin-top: 3mm !important; font-size: 7pt !important; }
            .qr-footer { margin-top: auto !important; padding-top: 2mm !important; }
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
        <p class="text-sm font-semibold text-neutral-800">Print this page — 6 QR cards per landscape A3 page. Choose A3, Landscape, 100% scale, and no margins.</p>
    </div>
    <button type="button" onclick="printSetupPack()" class="rounded-lg bg-black px-5 py-3 font-bold text-white">Print setup pack</button>
</div>

<main class="mx-auto grid max-w-6xl gap-4">
    @if($tables->isEmpty())
        <p class="rounded-xl bg-white p-5 font-semibold text-neutral-900">No active tables or rooms are available for this setup pack.</p>
    @else
        @foreach($tables->chunk(6) as $pageTables)
            <section class="qr-page">
                @foreach($pageTables as $table)
                    @php($menuUrl = route('menu.show', [$restaurant->slug, $table->table_number]))
                    <article class="qr-card flex flex-col items-center justify-between {{ $cardClass }} p-4 text-center" style="background-color: {{ $sticker['background_color'] }}; border-color: {{ $sticker['border_color'] }}; color: {{ $sticker['text_color'] }};">
                        <div class="flex w-full items-center justify-center gap-3">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $restaurant->name }} logo" class="qr-brand-logo h-12 w-14 object-contain">
                            @endif
                            <span class="qr-brand-name text-lg font-black leading-none" style="color: {{ $sticker['text_color'] }}">{{ $restaurant->name }}</span>
                        </div>
                        <p class="scan-label mt-1 text-[9px] font-black uppercase tracking-[.12em]" style="color: {{ $sticker['accent_color'] }}">{{ $table->isRoomServicePoint() ? $sticker['room_scan_text'] : $sticker['table_scan_text'] }}</p>
                        <img src="{{ $qrImages[$table->id] }}" alt="QR code for {{ $table->locationTypeLabel() }} {{ $table->table_number }}" class="qr-image h-28 w-28 contrast-125" data-print-resource width="112" height="112">
                        <div>
                            <p class="qr-type text-[10px] font-black uppercase" style="color: {{ $sticker['accent_color'] }}">{{ $table->locationTypeLabel() }}</p>
                            <p class="qr-location mt-1 text-xl font-black leading-tight" style="color: {{ $sticker['text_color'] }}">{{ $table->displayLabel() }}</p>
                            <p class="qr-url mt-1 break-all text-[8px] text-neutral-600">{{ $menuUrl }}</p>
                        </div>
                        <div class="qr-footer flex w-full items-center justify-center gap-1 border-t pt-1" style="border-color: {{ $sticker['border_color'] }};">
                            <span class="text-[8px] font-bold" style="color: {{ $sticker['text_color'] }}">Powered by</span>
                            <img src="{{ asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}" alt="ZemTab" class="h-5 w-auto">
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
