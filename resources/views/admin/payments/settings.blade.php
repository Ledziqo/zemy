@extends('layouts.dashboard', ['heading' => 'Payment Settings', 'eyebrow' => 'Admin Configuration'])

@section('content')
<div class="max-w-2xl rounded-md border border-zem-border bg-zem-card p-6">
    <h2 class="font-display text-xl font-bold">Payment method details</h2>
    <p class="mt-1 text-sm text-zem-muted">These details are shown to restaurants when their subscription expires or is about to expire. Edit them to update the payment instructions shown on the warning banner and the "please pay" page.</p>

    <form method="post" action="{{ route('admin.payment-settings.save') }}" class="mt-6 grid gap-4">
        @csrf
        @if(session('success'))
            <div class="rounded-md border border-zem-green/40 bg-zem-green/10 px-4 py-3 text-sm font-bold text-zem-green">{{ session('success') }}</div>
        @endif

        <label class="grid gap-2">
            <span class="text-sm font-bold text-zem-muted">Telebirr number</span>
            <input name="telebirr_number" value="{{ $settings['telebirr_number'] }}" class="rounded-md border border-zem-border bg-zem-bg px-4 py-3">
        </label>

        <label class="grid gap-2">
            <span class="text-sm font-bold text-zem-muted">CBE account number</span>
            <input name="cbe_account" value="{{ $settings['cbe_account'] }}" class="rounded-md border border-zem-border bg-zem-bg px-4 py-3">
        </label>

        <label class="grid gap-2">
            <span class="text-sm font-bold text-zem-muted">Awash Bank account</span>
            <input name="awash_account" value="{{ $settings['awash_account'] }}" class="rounded-md border border-zem-border bg-zem-bg px-4 py-3">
        </label>

        <label class="grid gap-2">
            <span class="text-sm font-bold text-zem-muted">Bank of Abyssinia account</span>
            <input name="abyssinia_account" value="{{ $settings['abyssinia_account'] }}" class="rounded-md border border-zem-border bg-zem-bg px-4 py-3">
        </label>

        <label class="grid gap-2">
            <span class="text-sm font-bold text-zem-muted">Telegram handle</span>
            <input name="telegram" value="{{ $settings['telegram'] }}" class="rounded-md border border-zem-border bg-zem-bg px-4 py-3">
            <span class="text-xs text-zem-muted">Restaurants send their payment screenshot with their restaurant name to this Telegram.</span>
        </label>

        <button class="rounded-md bg-zem-gold px-5 py-3 font-bold text-white">Save payment settings</button>
    </form>

    <div class="mt-6 border-t border-zem-border pt-4">
        <a href="{{ route('admin.payments.index') }}" class="text-sm font-bold text-zem-gold">← Back to payments</a>
    </div>
</div>
@endsection
