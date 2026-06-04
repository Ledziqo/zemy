@extends('layouts.dashboard', ['heading' => $restaurant->name, 'eyebrow' => 'Restaurant Overview', 'autoRefreshSeconds' => 10])

@section('content')
@include('restaurant.partials.order_sound_alerts', ['latestOrderId' => $latestOrderId])
<div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
    @foreach([['Today orders',$todayOrders],['All orders',$allOrders],['New',$newOrders],['Preparing',$preparingOrders],['Served / done',$servedOrders],['Completed',$completedOrders],['Revenue',number_format($revenue).' ETB']] as $card)
        <div class="rounded-md border border-zem-border bg-zem-card p-4"><p class="text-sm text-zem-muted">{{ $card[0] }}</p><p class="mt-2 text-2xl font-extrabold">{{ $card[1] }}</p></div>
    @endforeach
</div>
<section class="mt-6 rounded-md border border-zem-border bg-zem-card p-4">
    <div class="flex items-center justify-between"><h2 class="font-display text-xl font-bold">Recent orders</h2><a class="text-sm font-bold text-zem-gold" href="{{ route('restaurant.orders.index') }}">View all</a></div>
    <div class="mt-4 grid gap-3">
        @forelse($recentOrders as $order)
            <div class="rounded-md border border-zem-border bg-zem-bg p-4">
                <div class="flex flex-wrap justify-between gap-2"><strong>#{{ $order->id }} - Table {{ $order->table_number }}</strong><x-status :status="$order->status" /></div>
                <div class="mt-3 space-y-2">
                    @foreach($order->items as $item)
                        <p class="flex justify-between rounded-md bg-black px-3 py-2 text-sm">
                            <span>{{ $item->quantity }} x {{ $item->item_name }} @if($item->note)<em class="text-zem-muted">({{ $item->note }})</em>@endif</span>
                            <strong>{{ number_format($item->total_price) }} ETB</strong>
                        </p>
                    @endforeach
                </div>
                <p class="mt-3 text-right font-bold">{{ number_format($order->total) }} ETB</p>
            </div>
        @empty
            <p class="text-zem-muted">No orders yet. The sample menu is ready for table orders.</p>
        @endforelse
    </div>
    <p class="mt-5 text-sm text-zem-muted">Popular items analytics placeholder.</p>
</section>
@endsection
