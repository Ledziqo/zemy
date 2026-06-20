@extends('layouts.dashboard', ['heading' => $restaurant->name, 'eyebrow' => $restaurant->businessTypeLabel().' Overview'])

@section('content')
@include('restaurant.partials.order_sound_alerts', ['latestOrderId' => $latestOrderId])
@php($placeTitle = $restaurant->locationLabelTitle())
<div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
    @foreach([['Today orders',$todayOrders],['All orders',$allOrders],['New',$newOrders],['Preparing',$preparingOrders],['Served / done',$servedOrders],['Completed',$completedOrders]] as $card)
        <div class="rounded-md border border-zem-border bg-zem-card p-4"><p class="text-sm text-zem-muted">{{ $card[0] }}</p><p class="mt-2 text-2xl font-extrabold">{{ $card[1] }}</p></div>
    @endforeach
</div>
<div class="mt-4 rounded-md border border-zem-gold/30 bg-zem-gold/10 p-4"><p class="text-sm text-zem-muted">Revenue today</p><p class="mt-2 text-3xl font-extrabold text-zem-gold">{{ number_format($revenue) }} ETB</p></div>
<section class="mt-6 rounded-md border border-zem-border bg-zem-card p-4">
    <div class="flex items-center justify-between"><h2 class="font-display text-xl font-bold">Recent orders</h2><a class="text-sm font-bold text-zem-gold" href="{{ route('restaurant.orders.index') }}">Open work board</a></div>
    <div class="mt-4 grid gap-3">
        @forelse($recentOrders as $order)
            <div class="rounded-md border-l-4 border border-zem-border bg-zem-bg p-4 {{ $order->status === 'new' ? 'border-l-zem-gold' : ($order->status === 'preparing' ? 'border-l-blue-500' : ($order->status === 'served' ? 'border-l-green-500' : ($order->status === 'completed' ? 'border-l-gray-400' : 'border-l-zem-border'))) }}">
                <div class="flex flex-wrap justify-between gap-2"><strong>#{{ $order->id }} - {{ $placeTitle }} {{ $order->table_number }}</strong><x-status :status="$order->status" /></div>
                <div class="mt-3 space-y-2">
                    @foreach($order->items as $item)
                        <p class="flex justify-between gap-3 rounded-md bg-black px-3 py-2 text-sm text-white">
                            <span>{{ $item->quantity }} x {{ $item->item_name }} @if($item->note)<em class="text-white/70">({{ $item->note }})</em>@endif</span>
                            <strong class="shrink-0 text-white">{{ number_format($item->total_price) }} ETB</strong>
                        </p>
                    @endforeach
                </div>
                <p class="mt-3 text-right font-bold">{{ number_format($order->total) }} ETB</p>
            </div>
        @empty
            <div class="rounded-md border border-zem-border bg-zem-bg p-6 text-center">
                <div class="text-3xl mb-2">🍽️</div>
                <p class="text-zem-muted">No orders yet. The sample menu is ready for {{ $restaurant->locationLabel() }} orders.</p>
            </div>
        @endforelse
    </div>
</section>
@if($popularItems->isNotEmpty())
<section class="mt-6 rounded-md border border-zem-border bg-zem-card p-4">
    <h2 class="font-display text-xl font-bold">Popular items this week</h2>
    <div class="mt-4 grid gap-3">
        @foreach($popularItems as $item)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-md bg-zem-bg px-3 py-2 text-sm">
                <strong>{{ $item->item_name }}</strong>
                <span class="text-zem-muted">{{ number_format($item->quantity_sold) }} sold - {{ number_format($item->revenue_total) }} ETB</span>
            </div>
        @endforeach
    </div>
</section>
@endif
@endsection
