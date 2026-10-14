@extends('layouts.app', [
    'title' => $restaurant->name.' Information | ZemTab',
    'description' => 'Guest information for '.$restaurant->name.'.',
    'canonical' => \App\Support\PublicMenuCache::informationUrl($restaurant),
    'accentColor' => $restaurant->primary_color, 'robots' => 'noindex, follow',
])
@section('content')
<main style="max-width:720px;margin:0 auto;padding:32px 20px;">
    @php
        $logoPath = $restaurant->logo_path;
        $logoUrl = $logoPath ? (\Illuminate\Support\Str::startsWith($logoPath, ['http://', 'https://', 'uploads/'])
            ? (str_starts_with($logoPath, 'uploads/') ? asset($logoPath) : $logoPath) : asset('storage/'.$logoPath)) : null;
    @endphp
    @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $restaurant->name }} logo" style="max-width:220px;max-height:120px;object-fit:contain;margin-bottom:24px;">@endif
    <h1 style="font-size:28px;font-weight:700;">{{ $restaurant->name }}</h1>
    @php($board = $restaurant->settings['information_board'] ?? null)
    @if($board)
        <h2 style="font-size:20px;margin-top:24px;">{{ $board['title'] }}</h2>
        <nav style="display:flex;flex-wrap:wrap;gap:12px;margin:20px 0;" aria-label="Guide sections">
            @foreach($board['sections'] as $section)<a href="#guide-{{ $loop->index }}">{{ $section['title'] }}</a>@endforeach
        </nav>
        @foreach($board['sections'] as $section)
            <section id="guide-{{ $loop->index }}" style="padding:20px 0;border-top:1px solid #8884;scroll-margin-top:20px;">
                <h3 style="font-size:18px;font-weight:700;">{{ $section['title'] }}</h3>
                <p style="margin-top:12px;white-space:pre-wrap;">{{ $section['body'] }}</p>
            </section>
        @endforeach
    @else
        <h2 style="font-size:20px;margin-top:24px;">Guest information</h2>
        <p style="margin-top:16px;white-space:pre-wrap;">{{ $restaurant->settings['guest_information'] ?? 'Guest information will be added soon.' }}</p>
    @endif
</main>
@endsection
