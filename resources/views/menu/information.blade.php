@extends('layouts.app', [
    'title' => $restaurant->name.' Information | ZemTab',
    'description' => 'Guest information for '.$restaurant->name.'.',
    'canonical' => \App\Support\PublicMenuCache::informationUrl($restaurant),
    'accentColor' => $restaurant->primary_color, 'robots' => 'noindex, follow',
])
@section('content')
<main style="max-width:720px;margin:0 auto;padding:32px 20px;">
    <h1 style="font-size:28px;font-weight:700;">{{ $restaurant->name }}</h1>
    <h2 style="font-size:20px;margin-top:24px;">Guest information</h2>
    <p style="margin-top:16px;white-space:pre-wrap;">{{ $restaurant->settings['guest_information'] ?? 'Guest information will be added soon.' }}</p>
</main>
@endsection
