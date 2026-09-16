@extends('layouts.dashboard', ['heading' => $accessReason === 'payment' ? 'Payment Required' : 'Access Restricted', 'eyebrow' => 'Restaurant Access'])

@section('content')
<section class="mx-auto max-w-2xl rounded-md border border-zem-border bg-zem-card p-6">
    <div class="text-center">
        <div class="text-5xl mb-3">🔒</div>
        @if($accessReason === 'revoked')
            <h2 class="font-display text-2xl font-bold">Dashboard access revoked</h2>
            <p class="mt-3 text-zem-muted">{{ $restaurant->name }}'s dashboard access was manually revoked by an administrator. Please contact ZemTab support or your account manager to restore access.</p>
        @elseif($accessReason === 'inactive')
            <h2 class="font-display text-2xl font-bold">Account inactive</h2>
            <p class="mt-3 text-zem-muted">{{ $restaurant->name }}'s account is currently inactive. Please contact ZemTab support or your account manager.</p>
        @else
            <h2 class="font-display text-2xl font-bold">Payment required</h2>
            <p class="mt-3 text-zem-muted">{{ $restaurant->name }} can still be found by customers from existing QR menu links, but dashboard management is paused because the subscription needs payment. Pay the subscription to restore full access.</p>
        @endif
        @if($accessReason !== 'payment')
            <p class="mt-3 text-sm text-zem-muted">Contact ZemTab support on Telegram: <strong class="text-zem-cream">{{ config('payment.telegram') }}</strong></p>
        @endif
    </div>

    @if($accessReason === 'payment')
    <div class="mt-6 rounded-md border border-zem-border bg-zem-bg p-4">
        @php($sub = $restaurant->subscriptions()->latest('starts_at')->first())
        @if($sub)
            <div class="grid gap-2 text-sm">
                <div class="flex justify-between"><span class="text-zem-muted">Plan:</span><strong>{{ $sub->plan_name }}</strong></div>
                <div class="flex justify-between"><span class="text-zem-muted">Monthly price:</span><strong>{{ number_format($sub->monthly_price) }} ETB</strong></div>
                <div class="flex justify-between"><span class="text-zem-muted">Expired on:</span><strong>{{ $sub->ends_at ? \Carbon\Carbon::parse($sub->ends_at)->format('M j, Y') : 'N/A' }}</strong></div>
            </div>
        @else
            <p class="text-sm text-zem-muted">No subscription information available.</p>
        @endif
    </div>

    <div class="mt-6 rounded-md border border-zem-gold/40 bg-zem-gold/10 p-4">
        <h3 class="font-bold text-zem-gold">How to pay</h3>
        <div class="mt-3 grid gap-2 text-sm">
            <p>Telebirr: <strong class="text-zem-cream">{{ config('payment.telebirr') }}</strong></p>
            <p>CBE: <strong class="text-zem-cream">{{ config('payment.cbe') }}</strong></p>
            <p>Awash Bank: <strong class="text-zem-cream">{{ config('payment.awash') }}</strong></p>
            <p>Bank of Abyssinia: <strong class="text-zem-cream">{{ config('payment.abyssinia') }}</strong></p>
            <p>Telegram: <strong class="text-zem-cream">{{ config('payment.telegram') }}</strong></p>
        </div>
        <p class="mt-3 text-xs text-zem-muted">After paying, send your payment screenshot with your restaurant name to {{ config('payment.telegram') }} on Telegram. Access will be restored within minutes.</p>
    </div>
    @endif

    <form method="post" action="{{ route('logout') }}" class="mt-5">
        @csrf
        <button class="w-full rounded-md bg-zem-gold px-4 py-3 font-bold text-white">Logout</button>
    </form>
</section>
@endsection
