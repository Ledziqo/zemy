@extends('layouts.dashboard', ['heading' => 'Review clean start', 'eyebrow' => 'Admin · destructive action'])

@section('content')
<section class="max-w-3xl rounded-xl border border-red-400/50 bg-zem-card p-5">
    <h2 class="text-xl font-bold text-red-300">Review clean start for {{ $restaurant->name }}</h2>
    <p class="mt-2 text-sm text-zem-muted">This permanently clears this venue’s order history, order items, order/guest-linked payment records, service requests, and guest sessions. Cashier reports are calculated from orders and will therefore reset too.</p>
    <p class="mt-2 text-sm font-bold text-emerald-200">It keeps the menu, staff, subscriptions, room/table records, QR codes, and QR design settings. Subscription payment records are not included.</p>

    <div class="mt-5 grid gap-3 sm:grid-cols-2">
        @foreach([
            'orders' => 'Orders to delete',
            'order_items' => 'Order items to delete',
            'payments' => 'Order/guest payments to delete',
            'service_requests' => 'Service requests to delete',
            'guest_sessions' => 'Guest sessions to delete',
            'due_credits' => 'Outstanding credit orders',
        ] as $key => $label)
            <div class="rounded-lg border border-zem-border bg-zem-bg p-3">
                <p class="text-xs text-zem-muted">{{ $label }}</p>
                <p class="mt-1 font-bold">{{ number_format($counts[$key]) }}{{ $key === 'due_credits' ? ' · '.number_format($counts['due_credit_total'], 2).' ETB' : '' }}</p>
            </div>
        @endforeach
    </div>

    <form method="post" action="{{ url('/admin/database/workboard-reset') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="restaurant_id" value="{{ $restaurant->id }}">
        <label class="block text-sm">
            <span class="font-bold">Type <code>RESET {{ $restaurant->slug }}</code> to confirm</span>
            <input name="confirmation" required autocomplete="off" class="mt-2 w-full rounded-md border border-red-400/50 bg-zem-bg px-3 py-2">
            @error('confirmation')<span class="mt-1 block text-red-300">{{ $message }}</span>@enderror
        </label>
        <label class="flex items-start gap-2 text-sm text-zem-muted">
            <input type="checkbox" name="paused" value="1" required class="mt-1">
            <span>I have paused this venue’s operations and confirmed this is the correct venue. This deletion cannot be undone.</span>
        </label>
        @error('paused')<p class="text-sm text-red-300">Confirm the venue is paused before continuing.</p>@enderror
        <div class="flex flex-wrap gap-3">
            <button class="rounded-md bg-red-600 px-5 py-3 font-bold text-white">Permanently clear this venue’s workboard</button>
            <a href="{{ url('/admin/database') }}" class="rounded-md border border-zem-border px-5 py-3 font-bold">Cancel</a>
        </div>
    </form>
</section>
@endsection
