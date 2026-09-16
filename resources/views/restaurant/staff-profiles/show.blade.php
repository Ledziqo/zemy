@extends('layouts.dashboard', ['heading' => $profile->name, 'eyebrow' => $profile->roleLabel().' performance'])

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('restaurant.staff-profiles.index') }}" class="text-sm font-bold text-zem-gold">← Staff profiles</a>
            <p class="mt-2 text-sm text-zem-muted">Orders recorded under this profile. Sales include paid and completed orders.</p>
        </div>
        <span class="rounded-full border border-zem-border bg-zem-soft px-3 py-1 text-xs font-bold">{{ $profile->roleLabel() }}</span>
    </div>

    <form method="get" class="mb-5 flex flex-wrap items-end gap-3 rounded-lg border border-zem-border bg-zem-card p-4">
        <label class="grid gap-1 text-sm"><span class="font-bold">From</span><input type="date" name="date_from" value="{{ $date_from }}" class="rounded-md border border-zem-border bg-white px-3 py-2"></label>
        <label class="grid gap-1 text-sm"><span class="font-bold">To</span><input type="date" name="date_to" value="{{ $date_to }}" class="rounded-md border border-zem-border bg-white px-3 py-2"></label>
        <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Filter</button>
    </form>

    <div class="mb-5 grid gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-zem-border bg-zem-card p-4"><p class="text-xs text-zem-muted">Orders handled</p><p class="mt-1 text-2xl font-extrabold">{{ $orderCount }}</p></div>
        <div class="rounded-lg border border-zem-border bg-zem-card p-4"><p class="text-xs text-zem-muted">Sales collected</p><p class="mt-1 text-2xl font-extrabold">{{ number_format($salesTotal, 2) }} ETB</p></div>
        @if($restaurant->isHotel())
            <div class="rounded-lg border border-zem-gold/40 bg-zem-gold/10 p-4"><p class="text-xs text-zem-gold">Unpaid room credit</p><p class="mt-1 text-2xl font-extrabold text-zem-gold">{{ number_format($roomCreditTotal, 2) }} ETB</p></div>
        @endif
    </div>

    <section class="rounded-lg border border-zem-border bg-zem-card p-4">
        <h2 class="font-display text-lg font-bold">Orders handled by {{ $profile->name }}</h2>
        <div class="mt-4 space-y-3">
            @forelse($orders as $order)
                <article class="rounded-md border border-zem-border bg-zem-bg p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><p class="font-bold">Order #{{ $order->id }} · {{ $order->table?->displayLabel() ?: $order->table_number }}</p><p class="text-xs text-zem-muted">{{ $order->created_at->format('M j, Y · H:i') }}</p></div>
                        <div class="text-right"><p class="font-extrabold">{{ number_format($order->total, 2) }} ETB</p><p class="text-xs font-bold {{ $order->payment_method === 'room_credit' && $order->payment_status !== 'paid' ? 'text-zem-gold' : 'text-zem-muted' }}">{{ $order->payment_method === 'room_credit' && $order->payment_status !== 'paid' ? 'Room credit · unpaid' : ucfirst($order->payment_status ?? $order->status) }}</p></div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2 text-sm text-zem-muted">
                        @foreach($order->items as $item)<span class="rounded-full border border-zem-border px-2 py-1">{{ $item->quantity }} × {{ $item->item_name }}</span>@endforeach
                    </div>
                </article>
            @empty
                <p class="rounded-md border border-zem-border bg-zem-soft p-6 text-center text-sm text-zem-muted">No orders have been assigned to this profile in this date range yet.</p>
            @endforelse
        </div>
        <div class="mt-5">{{ $orders->links() }}</div>
    </section>
</div>
@endsection
