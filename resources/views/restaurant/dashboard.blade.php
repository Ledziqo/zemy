@extends('layouts.dashboard', ['heading' => $restaurant->name, 'eyebrow' => 'Restaurant Overview', 'autoRefreshSeconds' => 10])

@section('content')
<div class="grid gap-4 md:grid-cols-5">
    @foreach([['Today orders',$todayOrders],['New',$newOrders],['Preparing',$preparingOrders],['Served',$servedOrders],['Revenue',number_format($revenue).' ETB']] as $card)
        <div class="rounded-md border border-zem-border bg-zem-card p-4"><p class="text-sm text-zem-muted">{{ $card[0] }}</p><p class="mt-2 text-2xl font-extrabold">{{ $card[1] }}</p></div>
    @endforeach
</div>
<section class="mt-6 rounded-md border border-zem-border bg-zem-card p-4">
    <div class="flex items-center justify-between"><h2 class="font-display text-xl font-bold">Recent orders</h2><a class="text-sm font-bold text-zem-gold" href="{{ route('restaurant.orders.index') }}">View all</a></div>
    <div class="mt-4 grid gap-3">
        @forelse($recentOrders as $order)
            <div class="rounded-md border border-zem-border bg-zem-bg p-4"><div class="flex flex-wrap justify-between gap-2"><strong>#{{ $order->id }} · Table {{ $order->table_number }}</strong><x-status :status="$order->status" /></div><p class="mt-2 text-sm text-zem-muted">{{ $order->items->pluck('item_name')->join(', ') }}</p></div>
        @empty
            <p class="text-zem-muted">No orders yet. The sample menu is ready for table orders.</p>
        @endforelse
    </div>
    <p class="mt-5 text-sm text-zem-muted">Popular items analytics placeholder.</p>
</section>
@endsection
