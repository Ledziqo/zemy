@php
    $logoUrl = $restaurant->logo_path ? (\Illuminate\Support\Str::startsWith($restaurant->logo_path, ['http://', 'https://', 'uploads/']) ? (str_starts_with($restaurant->logo_path, 'uploads/') ? asset($restaurant->logo_path) : $restaurant->logo_path) : asset('storage/'.$restaurant->logo_path)) : null;
@endphp
<article class="signature-card" style="--card-bg:{{ $sticker['background_color'] }};--card-text:{{ $sticker['text_color'] }};--card-border:{{ $sticker['border_color'] }};--card-accent:{{ $sticker['accent_color'] }};--logo-size:{{ $sticker['logo_size'] }}mm;--text-size:{{ $sticker['text_size'] }}pt;--qr-size:{{ $sticker['qr_size'] }}mm;--detail-size:{{ $sticker['detail_size'] }}pt;--art-opacity:{{ $sticker['art_opacity'] / 100 }};">
    <svg class="signature-art" viewBox="0 0 297 560" preserveAspectRatio="none" aria-hidden="true">
        <path d="M148 0H297V160C243 151 255 91 211 75S169 38 148 0Z" fill="var(--card-accent)"/>
        <path d="M198 -20C171 36 320 63 275 149M214 -20C187 36 336 63 291 149M230 -20C203 36 352 63 307 149M246 -20C219 36 368 63 323 149" fill="none" stroke="var(--card-bg)" stroke-width="1.5" opacity=".65"/>
        <path d="M0 0H58L0 58Z" fill="var(--card-accent)" opacity=".12"/>
        <path d="M0 400C63 435 42 493 124 504S232 489 297 522V560H0Z" fill="var(--card-accent)"/>
        <path d="M-35 435C70 439 29 532 147 527S257 522 320 550M-35 445C70 449 29 542 147 537S257 532 320 560M-35 455C70 459 29 552 147 547S257 542 320 570" fill="none" stroke="var(--card-bg)" stroke-width="1.5" opacity=".7"/>
        <path d="M0 411C66 446 40 490 124 503" fill="none" stroke="var(--card-border)" stroke-width=".8" opacity=".3"/>
        <circle cx="282" cy="244" r="3" fill="var(--card-accent)"/>
        <path d="M12 167h12m-6-6v12" stroke="var(--card-accent)" stroke-width="1.3"/>
        <path d="M12 268v100M285 286v80" stroke="var(--card-border)" stroke-width=".6" opacity=".2"/>
    </svg>
    <div class="signature-logo-wrap">
        @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $restaurant->name }} logo" class="signature-logo" data-print-resource>@endif
    </div>
    <div class="signature-heading">
        <p class="signature-kicker">A LITTLE SCAN. A LOT TO ENJOY.</p>
        <p class="signature-title">{{ $scanText }}</p>
    </div>
    <div class="signature-scan">
        <div class="signature-frame"><img src="{{ $qrImage }}" alt="Menu QR code" width="500" height="500" data-print-resource></div>
        <p class="signature-hint">Open your camera · Scan · Enjoy</p>
    </div>
    <div class="signature-footer"><span>Powered by</span><img src="{{ asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}" alt="ZemTab" data-print-resource></div>
</article>
