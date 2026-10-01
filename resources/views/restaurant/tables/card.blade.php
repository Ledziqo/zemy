@php
    $logoPath = $sticker['qr_logo_path'] ?? $restaurant->logo_path;
    $logoUrl = $logoPath ? (\Illuminate\Support\Str::startsWith($logoPath, ['http://', 'https://', 'uploads/']) ? (str_starts_with($logoPath, 'uploads/') ? asset($logoPath) : $logoPath) : asset('storage/'.$logoPath)) : null;
    $elementStyle = function ($key) use ($sticker) {
        $e = $sticker['elements'][$key] ?? [];
        return 'translate:'.(float)($e['x'] ?? 0).'mm '.(float)($e['y'] ?? 0).'mm;scale:'.(float)($e['sx'] ?? 1).' '.(float)($e['sy'] ?? 1).';rotate:'.(float)($e['r'] ?? 0).'deg;';
    };
    $locationLabel = $locationLabel ?? (($table ?? null)?->displayLabel());
@endphp
<article class="signature-card{{ ($sticker['orientation'] ?? 'portrait') === 'landscape' ? ' is-landscape' : '' }}" style="--qr-bg:{{ $sticker['qr_background_color'] ?? '#FFFFFF' }};--card-bg:{{ $sticker['background_color'] }};--card-text:{{ $sticker['text_color'] }};--card-border:{{ $sticker['border_color'] }};--card-accent:{{ $sticker['accent_color'] }};--logo-size:{{ $sticker['logo_size'] }}mm;--logo-half-height:{{ ($sticker['logo_size'] ?? 24) / 2 }}mm;--logo-width:{{ $sticker['logo_width'] ?? 53 }}mm;--logo-half-width:{{ ($sticker['logo_width'] ?? 53) / 2 }}mm;--logo-x:{{ $sticker['logo_x'] ?? 37 }}mm;--logo-y:{{ $sticker['logo_y'] ?? 18 }}mm;--heading-x:{{ $sticker['heading_x'] ?? 0 }}mm;--heading-y:{{ $sticker['heading_y'] ?? 0 }}mm;--kicker-x:{{ $sticker['kicker_x'] ?? 0 }}mm;--kicker-y:{{ $sticker['kicker_y'] ?? 0 }}mm;--title-x:{{ $sticker['title_x'] ?? 0 }}mm;--title-y:{{ $sticker['title_y'] ?? 0 }}mm;--location-x:{{ $sticker['location_x'] ?? 0 }}mm;--location-y:{{ $sticker['location_y'] ?? 0 }}mm;--scan-x:{{ $sticker['scan_x'] ?? 0 }}mm;--scan-y:{{ $sticker['scan_y'] ?? -3 }}mm;--frame-x:{{ $sticker['frame_x'] ?? 0 }}mm;--frame-y:{{ $sticker['frame_y'] ?? 0 }}mm;--hint-x:{{ $sticker['hint_x'] ?? 0 }}mm;--hint-y:{{ $sticker['hint_y'] ?? 0 }}mm;--footer-x:{{ $sticker['footer_x'] ?? 0 }}mm;--footer-y:{{ $sticker['footer_y'] ?? 0 }}mm;--footer-scale:{{ ($sticker['footer_size'] ?? 100) / 100 }};--text-size:{{ $sticker['text_size'] }}pt;--qr-size:{{ $sticker['qr_size'] }}mm;--detail-size:{{ $sticker['detail_size'] }}pt;--art-opacity:{{ $sticker['art_opacity'] / 100 }};">
    <svg class="signature-art" data-layer="art" style="{{ $elementStyle('art') }}" viewBox="{{ ($sticker['orientation'] ?? 'portrait') === 'landscape' ? '0 0 560 297' : '0 0 297 560' }}" preserveAspectRatio="none" aria-hidden="true">
        <g class="signature-artwork-landscape" @if(($sticker['orientation'] ?? 'portrait') !== 'landscape') style="display:none" @endif>
   <path d="M0 0H560V297H0Z" fill="var(--card-bg)"/>
   <path d="M63 275C169 224 237 300 366 281L410 297H63Z" fill="var(--card-border)" opacity=".22"/>
   <path d="M63 285C169 234 237 310 401 285" fill="none" stroke="var(--card-border)" stroke-width=".7"/>
   <path d="M0 0H63V297H0Z" fill="var(--card-accent)"/>
   <path d="M53 0V297M47 0V297" stroke="var(--card-border)" stroke-width=".65"/>
   <path d="M333 0H560V297H385C305 263 293 216 320 165S375 64 333 0Z" fill="var(--card-accent)"/>
   <path d="M325 0C367 64 339 111 312 163S297 265 377 297" fill="none" stroke="var(--card-border)" stroke-width="1"/>
   <path d="M560 29C482 -1 447 32 414 76M560 38C482 8 454 40 421 84M560 47C482 17 461 48 428 92" fill="none" stroke="var(--card-border)" stroke-width=".6"/>
   <path d="M63 0H271C214 24 139 8 63 56Z" fill="var(--card-border)" opacity=".22"/>
   </g>
        <g class="signature-artwork-portrait" @if(($sticker['orientation'] ?? 'portrait') === 'landscape') style="display:none" @endif>
   <path d="M0 0H297V560H0Z" fill="var(--card-bg)"/>
   <path d="M0 0H297V44H0Z" fill="var(--card-accent)"/>
   <path d="M0 36H297M0 40H297" stroke="var(--card-border)" stroke-width=".65"/>
   <path d="M0 294C92 330 219 256 297 303V530C190 559 87 506 0 530Z" fill="var(--card-accent)"/>
   <path d="M0 273C92 309 219 235 297 282V298C219 251 92 325 0 289Z" fill="var(--card-border)" opacity=".13"/>
   <path d="M0 282C92 318 219 244 297 291" fill="none" stroke="var(--card-border)" stroke-width="1"/>
   <path d="M0 553C87 535 190 577 297 546V560H0Z" fill="var(--card-bg)"/>
   <path d="M0 544C87 526 190 568 297 537" fill="none" stroke="var(--card-border)" stroke-width="1.35"/>
   <path d="M0 552C87 534 190 576 297 545" fill="none" stroke="var(--card-border)" stroke-width=".9"/>
   <path d="M23 83V188M274 83V188" stroke="var(--card-border)" stroke-width=".6"/></g>
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
    @if(!empty($locationLabel ?? null))<p class="signature-location" data-layer="location" style="{{ $elementStyle('location') }}">@php($labelParts = preg_split('/\s+(?=\S+$)/u', trim($locationLabel), 2))<span>{{ $labelParts[0] }}</span>@if(isset($labelParts[1]))<strong>{{ $labelParts[1] }}</strong>@endif</p>@endif
    <div class="signature-scan">
        <div class="signature-frame" data-layer="frame" style="{{ $elementStyle('frame') }}">
            <img src="{{ $qrImage }}" alt="Menu QR code" width="500" height="500" data-print-resource>
            <svg class="signature-frame-detail" viewBox="0 0 440 440" preserveAspectRatio="none" aria-hidden="true">
                <rect x="8" y="8" width="424" height="424" rx="10" fill="none" stroke-opacity=".55" stroke-width=".9"/>
                <path d="M204 8H236M432 204V236M204 432H236M8 204V236" fill="none" stroke-width="2.8" stroke-linecap="round"/>
            </svg>
        </div>
        <p class="signature-hint" data-layer="hint" style="{{ $elementStyle('hint') }}">Scan. Tap. Enjoy.</p>
    </div>
    <div class="signature-footer" data-layer="footer" style="{{ $elementStyle('footer') }}"><img src="{{ asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}" alt="ZemTab" data-print-resource></div>
</article>
