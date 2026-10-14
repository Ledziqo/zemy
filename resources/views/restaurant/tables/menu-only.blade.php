@extends('layouts.dashboard', ['heading' => __('Menu QR')])

@section('content')
<section class="max-w-3xl rounded-xl border border-zem-border bg-zem-card p-6">
    <h2 class="font-display text-xl font-bold">{{ __('Your digital menu QR') }}</h2>
    <p class="mt-2 text-zem-muted">{{ __('Use this same QR throughout your venue. Guests can browse your current menu and prices. Menu updates keep the same QR code.') }}</p>
    <img src="{{ route('restaurant.tables.menu-qr') }}" alt="{{ $restaurant->name }} menu QR" width="240" height="240" class="mt-5 h-60 w-60 rounded-xl border border-zem-border bg-white p-3">
    <p class="mt-4 break-all text-sm text-zem-muted">{{ route('menu.catalog', $restaurant->slug) }}</p>
    <div class="mt-5 flex flex-wrap gap-3">
        <a href="{{ route('restaurant.tables.menu-qr') }}" class="rounded-lg bg-zem-gold px-5 py-3 font-bold text-white">{{ __('Download menu QR') }}</a>
        <a href="{{ route('menu.catalog', $restaurant->slug) }}" target="_blank" rel="noopener" class="rounded-lg border border-zem-border px-5 py-3 font-bold">{{ __('Open menu') }}</a>
    </div>
</section>
@endsection
