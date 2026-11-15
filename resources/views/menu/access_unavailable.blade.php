@extends('layouts.app', ['title' => $restaurant->name.' | Service unavailable', 'robots' => 'noindex, nofollow'])
@section('content')
<main style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:24px;background:#f7f7f5;">
<section style="max-width:480px;text-align:center;background:white;border:1px solid #ddd;border-radius:16px;padding:32px;color:#242424;">
<h1 style="font-size:24px;font-weight:700;">{{ $restaurant->name }}</h1>
<h2 style="font-size:18px;margin-top:20px;">Temporarily unavailable</h2>
<p style="margin-top:12px;line-height:1.6;">Please contact the venue for its menu and guest information.</p>
</section></main>
@endsection
