@extends('layouts.dashboard', ['heading' => __('Menu QR')])

@section('content')
@include('restaurant.tables.shared-print-options')
@include('restaurant.tables.design-settings')
<section class="max-w-3xl rounded-xl border border-zem-border bg-zem-card p-6">
    <h2 class="font-display text-xl font-bold">{{ __('Your digital menu QR') }}</h2>
    <p class="mt-2 text-zem-muted">{{ __('Use this same QR throughout your venue. Guests can browse your current menu and prices. Menu updates keep the same QR code.') }}</p>

    <div class="mt-5 flex flex-wrap gap-3">
        <a href="{{ route('restaurant.tables.index') }}" class="rounded-lg border border-zem-border px-5 py-3 font-bold">Menu card studio</a>
        @if($restaurant->informationBoardEnabled())
        <a href="{{ route('restaurant.tables.index', ['card' => 'information']) }}" class="rounded-lg border border-zem-border px-5 py-3 font-bold">Information card studio</a>
        @endif
    </div>
    <p class="mt-4 break-all text-sm text-zem-muted">{{ $menuUrl }}</p>
    <div class="mt-5 flex flex-wrap gap-3">
        <a href="{{ $menuUrl }}" target="_blank" rel="noopener" class="rounded-lg border border-zem-border px-5 py-3 font-bold">{{ __('Open menu') }}</a>
    </div>
</section>
@endsection
