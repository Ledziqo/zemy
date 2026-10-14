@extends('layouts.app', [
    'title' => $restaurant->name.' Hotel Guide | ZemTab',
    'description' => 'Guest information for '.$restaurant->name.'.',
    'canonical' => \App\Support\PublicMenuCache::informationUrl($restaurant),
    'accentColor' => $restaurant->primary_color, 'robots' => 'noindex, follow', 'alpine' => true,
])
@section('content')
@php
    $board = $restaurant->settings['information_board'] ?? null;
    $logoWidth = max(80, min(400, (int) ($board['logo_width'] ?? 200)));
    $logoPath = $board['logo_path'] ?? $restaurant->logo_path;
    $logoUrl = $logoPath ? (\Illuminate\Support\Str::startsWith($logoPath, ['http://', 'https://', 'uploads/'])
        ? (str_starts_with($logoPath, 'uploads/') ? asset($logoPath) : $logoPath) : asset('storage/'.$logoPath)) : null;
@endphp
<style>
.hotel-guide{background:#f7f6f2;color:#242420;min-height:100vh;font-family:'Segoe UI',Arial,sans-serif}.guide-shell{max-width:1040px;margin:auto;padding:0 24px}.guide-hero{background:linear-gradient(145deg,#fff,#f0eadc);border-bottom:1px solid #ded8ca;padding:42px 0 36px}.guide-brand{display:flex;align-items:center;gap:26px}.guide-logo{width:var(--guide-logo-width,200px);max-width:100%;height:auto;object-fit:contain;flex-shrink:0}.guide-eyebrow{font-size:11px;letter-spacing:.18em;text-transform:uppercase;font-weight:700;color:#927742;margin-bottom:10px}.guide-hero h1{font-size:clamp(28px,4vw,42px);line-height:1.15;font-weight:700;letter-spacing:-.035em}.guide-hero p{margin-top:12px;color:#68665e;line-height:1.7;max-width:560px}.guide-tools{padding:24px 0 0}.guide-search{width:100%;padding:15px 18px;border:1px solid #ded8ca;border-radius:12px;background:white;color:#242420;font-size:15px;outline-offset:4px}.guide-jumps{display:flex;gap:8px;overflow-x:auto;padding:16px 0;scrollbar-width:thin}.guide-jumps a{white-space:nowrap;border:1px solid #ded8ca;border-radius:999px;padding:8px 14px;color:#5f5540;font-size:12px;background:#fff;text-decoration:none}.guide-content{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;padding:10px 0 20px}.guide-card{background:#fff;border:1px solid #e7e3d9;border-radius:16px;padding:25px;box-shadow:0 4px 16px #332a1404;scroll-margin-top:24px}.guide-card:first-child{grid-column:1/-1}.guide-card h2{font-size:18px;line-height:1.4;font-weight:700;display:flex;gap:12px;align-items:center;margin-bottom:16px}.guide-number{font-size:11px;color:#927742;font-weight:600;border:1px solid #e9e0ce;border-radius:8px;padding:5px 7px;flex-shrink:0}.guide-copy{white-space:pre-line;font-size:14px;line-height:1.85;color:#626159;overflow-wrap:anywhere}.guide-empty{padding:32px;text-align:center;color:#68665e}@media(max-width:640px){.guide-shell{padding:0 18px}.guide-hero{padding:28px 0}.guide-brand{flex-direction:column;align-items:flex-start;gap:22px}.guide-logo{max-width:100%}.guide-content{grid-template-columns:1fr;gap:14px}.guide-card{padding:22px}.guide-hero h1{font-size:30px}}
</style>
<main class="hotel-guide" x-data="{ search: '', matches(title, body) { const q = this.search.trim().toLowerCase(); return !q || (title + ' ' + body).toLowerCase().includes(q); } }">
    <header class="guide-hero"><div class="guide-shell guide-brand">
        @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $restaurant->name }} logo" class="guide-logo" style="--guide-logo-width:{{ $logoWidth }}px">@endif
        <div><div class="guide-eyebrow">{{ $restaurant->name }}</div><h1>{{ $board['title'] ?? 'Hotel Guide' }}</h1><p>Everything you need for a comfortable stay. Explore hotel services, useful contacts and guest information.</p></div>
    </div></header>
    <div class="guide-shell">
    @if($board)
        <div class="guide-tools js-only"><label for="guide-search" class="sr-only">Search hotel information</label><input id="guide-search" class="guide-search" type="search" x-model="search" placeholder="Search breakfast, checkout, room service…" autocomplete="off"></div>
        <nav class="guide-jumps" aria-label="Hotel guide sections">@foreach($board['sections'] as $section)<a href="#guide-{{ $loop->index }}" @click="search = ''">{{ $section['title'] }}</a>@endforeach</nav>
        <div class="guide-content">
        @foreach($board['sections'] as $section)
            <section class="guide-card" id="guide-{{ $loop->index }}" x-show="matches(@js($section['title']), @js($section['body']))">
                <h2><span class="guide-number" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>{{ $section['title'] }}</h2>
                <p class="guide-copy">{{ $section['body'] }}</p>
            </section>
        @endforeach
        </div>
        <p class="guide-empty js-only" x-show="!@js(collect($board['sections'])->values()).some(section => matches(section.title, section.body))">No matching information. Try another search.</p>
    @else
        <div class="guide-content"><section class="guide-card"><h2>Guest information</h2><p class="guide-copy">{{ $restaurant->settings['guest_information'] ?? 'Guest information will be added soon.' }}</p></section></div>
    @endif
    @include('menu.powered-by')
    </div>
</main>
@endsection
