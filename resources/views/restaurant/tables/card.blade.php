@php
    $logoPath = $sticker['qr_logo_path'] ?? $restaurant->logo_path;
    $logoUrl = $logoPath ? (\Illuminate\Support\Str::startsWith($logoPath, ['http://', 'https://', 'uploads/']) ? (str_starts_with($logoPath, 'uploads/') ? asset($logoPath) : $logoPath) : asset('storage/'.$logoPath)) : null;
    $elementStyle = function ($key) use ($sticker) {
        $e = $sticker['elements'][$key] ?? [];
        return 'translate:'.(float)($e['x'] ?? 0).'mm '.(float)($e['y'] ?? 0).'mm;scale:'.(float)($e['sx'] ?? 1).' '.(float)($e['sy'] ?? 1).';';
    };
    $locationLabel = $locationLabel ?? (($table ?? null)?->displayLabel());
@endphp
<article class="signature-card{{ ($sticker['orientation'] ?? 'portrait') === 'landscape' ? ' is-landscape' : '' }}" style="--card-bg:{{ $sticker['background_color'] }};--card-text:{{ $sticker['text_color'] }};--card-border:{{ $sticker['border_color'] }};--card-accent:{{ $sticker['accent_color'] }};--logo-size:{{ $sticker['logo_size'] }}mm;--logo-half-height:{{ ($sticker['logo_size'] ?? 24) / 2 }}mm;--logo-width:{{ $sticker['logo_width'] ?? 53 }}mm;--logo-half-width:{{ ($sticker['logo_width'] ?? 53) / 2 }}mm;--logo-x:{{ $sticker['logo_x'] ?? 37 }}mm;--logo-y:{{ $sticker['logo_y'] ?? 18 }}mm;--heading-x:{{ $sticker['heading_x'] ?? 0 }}mm;--heading-y:{{ $sticker['heading_y'] ?? 0 }}mm;--kicker-x:{{ $sticker['kicker_x'] ?? 0 }}mm;--kicker-y:{{ $sticker['kicker_y'] ?? 0 }}mm;--title-x:{{ $sticker['title_x'] ?? 0 }}mm;--title-y:{{ $sticker['title_y'] ?? 0 }}mm;--location-x:{{ $sticker['location_x'] ?? 0 }}mm;--location-y:{{ $sticker['location_y'] ?? 0 }}mm;--scan-x:{{ $sticker['scan_x'] ?? 0 }}mm;--scan-y:{{ $sticker['scan_y'] ?? -3 }}mm;--frame-x:{{ $sticker['frame_x'] ?? 0 }}mm;--frame-y:{{ $sticker['frame_y'] ?? 0 }}mm;--hint-x:{{ $sticker['hint_x'] ?? 0 }}mm;--hint-y:{{ $sticker['hint_y'] ?? 0 }}mm;--footer-x:{{ $sticker['footer_x'] ?? 0 }}mm;--footer-y:{{ $sticker['footer_y'] ?? 0 }}mm;--footer-scale:{{ ($sticker['footer_size'] ?? 100) / 100 }};--text-size:{{ $sticker['text_size'] }}pt;--qr-size:{{ $sticker['qr_size'] }}mm;--detail-size:{{ $sticker['detail_size'] }}pt;--art-opacity:{{ $sticker['art_opacity'] / 100 }};">
    <svg class="signature-art" data-layer="art" style="{{ $elementStyle('art') }}" viewBox="{{ ($sticker['orientation'] ?? 'portrait') === 'landscape' ? '0 0 560 297' : '0 0 297 560' }}" preserveAspectRatio="none" aria-hidden="true">
        <g class="signature-artwork-landscape" @if(($sticker['orientation'] ?? 'portrait') !== 'landscape') style="display:none" @endif>
        <path d="M0 0H560V297H0Z" fill="var(--card-bg)"/>
        <path d="M305 0H560V106C485 83 450 54 401 34S348 12 305 0Z" fill="var(--card-accent)" opacity=".13"/>
        <path d="M335 0H560V91C499 76 465 53 425 33S365 7 335 0Z" fill="var(--card-accent)"/>
        <path d="M372 -8C422 28 480 42 568 68M389 -11C439 25 497 39 585 65M406 -14C456 22 514 36 602 62" fill="none" stroke="var(--card-bg)" stroke-width=".95" opacity=".72"/>
        <path d="M0 214C88 225 140 259 237 261S429 242 560 267V297H0Z" fill="var(--card-accent)" opacity=".13"/>
        <path d="M0 230C97 242 144 274 240 274S429 253 560 277V297H0Z" fill="var(--card-accent)"/>
        <path d="M-8 246C97 258 145 286 244 286S433 266 568 290M-8 253C97 265 145 293 244 293S433 273 568 297M-8 260C97 272 145 300 244 300S433 280 568 304" fill="none" stroke="var(--card-bg)" stroke-width=".85" opacity=".7"/>
        <path d="M0 0H113L0 34Z" fill="var(--card-accent)" opacity=".12"/>
        <path d="M0 23L76 0" fill="none" stroke="var(--card-accent)" stroke-width=".7" opacity=".35"/>
        </g>
        <g class="signature-artwork-portrait" @if(($sticker['orientation'] ?? 'portrait') === 'landscape') style="display:none" @endif>
        <path d="M155 0H297V173C241 155 264 95 217 73S174 26 155 0Z" fill="var(--card-accent)" opacity=".13"/>
        <path d="M176 0H297V152C255 131 272 85 231 64S192 20 176 0Z" fill="var(--card-accent)"/>
        <path d="M214 -16C191 35 320 59 285 145M228 -18C205 33 334 57 299 143M242 -20C219 31 348 55 313 141" fill="none" stroke="var(--card-bg)" stroke-width="1.1" opacity=".7"/>
        <path d="M0 0H68L0 60Z" fill="var(--card-accent)" opacity=".12"/>
        <path d="M0 42L47 0" fill="none" stroke="var(--card-accent)" stroke-width=".8" opacity=".35"/>
        <path d="M0 426C67 449 58 492 135 503S242 490 297 518V560H0Z" fill="var(--card-accent)" opacity=".13"/>
        <path d="M0 449C61 465 61 510 138 519S246 508 297 537V560H0Z" fill="var(--card-accent)"/>
        <path d="M-12 472C56 485 65 533 143 536S250 526 310 554M-12 481C56 494 65 542 143 545S250 535 310 563M-12 490C56 503 65 551 143 554S250 544 310 572" fill="none" stroke="var(--card-bg)" stroke-width="1" opacity=".7"/>
        </g>
    </svg>
    <div class="signature-logo-slot" aria-hidden="true"></div>
    <div class="signature-logo-wrap">
        @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $restaurant->name }} logo" class="signature-logo" data-layer="logo" style="{{ $elementStyle('logo') }}" data-print-resource>@endif
        <span class="logo-resize-handle no-print" data-resize="width" title="Drag to resize logo width" aria-label="Drag to resize logo width"></span>
        <span class="logo-resize-handle no-print" data-resize="height" title="Drag to resize logo height" aria-label="Drag to resize logo height"></span>
        <span class="logo-resize-handle no-print" data-resize="both" title="Drag to resize logo width and height" aria-label="Drag to resize logo width and height"></span>
    </div>
    <div class="signature-heading">
        <p class="signature-kicker"><span class="signature-kicker-cross" data-layer="cross" style="{{ $elementStyle('cross') }}" aria-hidden="true">＋</span><span class="signature-kicker-line" data-layer="line_left" style="{{ $elementStyle('line_left') }}" aria-hidden="true"></span><span class="signature-kicker-text" data-layer="kicker_text" style="{{ $elementStyle('kicker_text') }}">AT YOUR SERVICE</span><span class="signature-kicker-line" data-layer="line_right" style="{{ $elementStyle('line_right') }}" aria-hidden="true"></span></p>
        <p class="signature-title" data-layer="title" style="{{ $elementStyle('title') }}">{{ $scanText }}</p>
    </div>
    @if(!empty($locationLabel ?? null))<p class="signature-location" data-layer="location" style="{{ $elementStyle('location') }}">{{ $locationLabel }}</p>@endif
    <div class="signature-scan">
        <div class="signature-frame" data-layer="frame" style="{{ $elementStyle('frame') }}"><img src="{{ $qrImage }}" alt="Menu QR code" width="500" height="500" data-print-resource></div>
        <p class="signature-hint" data-layer="hint" style="{{ $elementStyle('hint') }}">Scan. Tap. Enjoy.</p>
    </div>
    <div class="signature-footer" data-layer="footer" style="{{ $elementStyle('footer') }}"><span>Powered by</span><img src="{{ asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}" alt="ZemTab" data-print-resource></div>
</article>
