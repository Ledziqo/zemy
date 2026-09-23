@php
    $logoPath = $sticker['qr_logo_path'] ?? $restaurant->logo_path;
    $logoUrl = $logoPath ? (\Illuminate\Support\Str::startsWith($logoPath, ['http://', 'https://', 'uploads/']) ? (str_starts_with($logoPath, 'uploads/') ? asset($logoPath) : $logoPath) : asset('storage/'.$logoPath)) : null;
    $elementStyle = function ($key) use ($sticker) {
        $e = $sticker['elements'][$key] ?? [];
        return 'translate:'.(float)($e['x'] ?? 0).'mm '.(float)($e['y'] ?? 0).'mm;scale:'.(float)($e['sx'] ?? 1).' '.(float)($e['sy'] ?? 1).';';
    };
    $locationLabel = $locationLabel ?? (($table ?? null)?->displayLabel());
@endphp
<article class="signature-card" style="--card-bg:{{ $sticker['background_color'] }};--card-text:{{ $sticker['text_color'] }};--card-border:{{ $sticker['border_color'] }};--card-accent:{{ $sticker['accent_color'] }};--logo-size:{{ $sticker['logo_size'] }}mm;--logo-half-height:{{ ($sticker['logo_size'] ?? 24) / 2 }}mm;--logo-width:{{ $sticker['logo_width'] ?? 53 }}mm;--logo-half-width:{{ ($sticker['logo_width'] ?? 53) / 2 }}mm;--logo-x:{{ $sticker['logo_x'] ?? 37 }}mm;--logo-y:{{ $sticker['logo_y'] ?? 18 }}mm;--heading-x:{{ $sticker['heading_x'] ?? 0 }}mm;--heading-y:{{ $sticker['heading_y'] ?? 0 }}mm;--kicker-x:{{ $sticker['kicker_x'] ?? 0 }}mm;--kicker-y:{{ $sticker['kicker_y'] ?? 0 }}mm;--title-x:{{ $sticker['title_x'] ?? 0 }}mm;--title-y:{{ $sticker['title_y'] ?? 0 }}mm;--location-x:{{ $sticker['location_x'] ?? 0 }}mm;--location-y:{{ $sticker['location_y'] ?? 0 }}mm;--scan-x:{{ $sticker['scan_x'] ?? 0 }}mm;--scan-y:{{ $sticker['scan_y'] ?? -3 }}mm;--frame-x:{{ $sticker['frame_x'] ?? 0 }}mm;--frame-y:{{ $sticker['frame_y'] ?? 0 }}mm;--hint-x:{{ $sticker['hint_x'] ?? 0 }}mm;--hint-y:{{ $sticker['hint_y'] ?? 0 }}mm;--footer-x:{{ $sticker['footer_x'] ?? 0 }}mm;--footer-y:{{ $sticker['footer_y'] ?? 0 }}mm;--footer-scale:{{ ($sticker['footer_size'] ?? 100) / 100 }};--text-size:{{ $sticker['text_size'] }}pt;--qr-size:{{ $sticker['qr_size'] }}mm;--detail-size:{{ $sticker['detail_size'] }}pt;--art-opacity:{{ $sticker['art_opacity'] / 100 }};">
    <svg class="signature-art" data-layer="art" style="{{ $elementStyle('art') }}" viewBox="0 0 297 560" preserveAspectRatio="none" aria-hidden="true">
        <path d="M148 0H297V160C243 151 255 91 211 75S169 38 148 0Z" fill="var(--card-accent)"/>
        <path d="M198 -20C171 36 320 63 275 149M214 -20C187 36 336 63 291 149M230 -20C203 36 352 63 307 149M246 -20C219 36 368 63 323 149" fill="none" stroke="var(--card-bg)" stroke-width="1.5" opacity=".65"/>
        <path d="M0 0H58L0 58Z" fill="var(--card-accent)" opacity=".12"/>
        <path d="M0 400C63 435 42 493 124 504S232 489 297 522V560H0Z" fill="var(--card-accent)"/>
        <path d="M-35 435C70 439 29 532 147 527S257 522 320 550M-35 445C70 449 29 542 147 537S257 532 320 560M-35 455C70 459 29 552 147 547S257 542 320 570" fill="none" stroke="var(--card-bg)" stroke-width="1.5" opacity=".7"/>
        <path d="M0 411C66 446 40 490 124 503" fill="none" stroke="var(--card-border)" stroke-width=".8" opacity=".3"/>
        <circle cx="282" cy="244" r="3" fill="var(--card-accent)"/>
        <path d="M12 268v100M285 286v80" stroke="var(--card-border)" stroke-width=".6" opacity=".2"/>
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
        @if(!empty($locationLabel ?? null))<p class="signature-location" data-layer="location" style="{{ $elementStyle('location') }}">{{ $locationLabel }}</p>@endif
    </div>
    <div class="signature-scan">
        <div class="signature-frame" data-layer="frame" style="{{ $elementStyle('frame') }}"><img src="{{ $qrImage }}" alt="Menu QR code" width="500" height="500" data-print-resource></div>
        <p class="signature-hint" data-layer="hint" style="{{ $elementStyle('hint') }}">Scan. Tap. Enjoy.</p>
    </div>
    <div class="signature-footer" data-layer="footer" style="{{ $elementStyle('footer') }}"><span data-layer="credit" style="{{ $elementStyle('credit') }}">Powered by</span><img data-layer="zemtab" style="{{ $elementStyle('zemtab') }}" src="{{ asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}" alt="ZemTab" data-print-resource></div>
</article>
