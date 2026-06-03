@extends('layouts.dashboard', ['heading' => 'Payment Required', 'eyebrow' => 'Restaurant Access'])

@section('content')
<section class="max-w-3xl rounded-md border border-zem-border bg-zem-card p-6">
    <x-status :status="$restaurant->dashboard_access_status" />
    <h2 class="mt-4 font-display text-2xl font-bold">Dashboard access is currently limited</h2>
    <p class="mt-3 text-zem-muted">
        {{ $restaurant->name }} can still be found by customers from existing QR menu links, but dashboard management is paused.
        Please contact ZemTab support to settle the subscription and restore access.
    </p>
    <div class="mt-5 rounded-md border border-zem-border bg-zem-bg p-4 text-sm text-zem-muted">
        <p>Subscription status: <strong class="text-white">{{ $subscription?->status ?? 'not set' }}</strong></p>
        <p>Access status: <strong class="text-white">{{ str_replace('_', ' ', $restaurant->dashboard_access_status) }}</strong></p>
    </div>
    <form method="post" action="{{ route('logout') }}" class="mt-5">
        @csrf
        <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Logout</button>
    </form>
</section>
@endsection
