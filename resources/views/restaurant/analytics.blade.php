@extends('layouts.dashboard', ['heading' => 'Analytics', 'eyebrow' => $restaurant->businessTypeLabel().' Growth'])

@section('content')
@php($placeTitle = $restaurant->locationLabelTitle())
<div class="grid gap-4 md:grid-cols-5">
    @foreach([['Today orders',$todayOrders],['Today revenue',number_format($todayRevenue).' ETB'],['30-day orders',$last30Orders],['30-day revenue',number_format($last30Revenue).' ETB'],['Completed',$completedOrders]] as $card)
        <div class="rounded-md border border-zem-border bg-zem-card p-4"><p class="text-sm text-zem-muted">{{ $card[0] }}</p><p class="mt-2 text-2xl font-extrabold">{{ $card[1] }}</p></div>
    @endforeach
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <section class="rounded-md border border-zem-border bg-zem-card p-4">
        <h2 class="font-display text-xl font-bold">Top-selling items</h2>
        <div class="mt-4 grid gap-3">
            @forelse($topItems as $item)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-md bg-zem-bg px-3 py-2 text-sm">
                    <strong>{{ $item->item_name }}</strong>
                    <span class="text-zem-muted">{{ number_format($item->quantity_sold) }} sold - {{ number_format($item->revenue_total) }} ETB</span>
                </div>
            @empty
                <p class="text-sm text-zem-muted">No item sales in the last 30 days yet.</p>
            @endforelse
        </div>
    </section>

    <section class="rounded-md border border-zem-border bg-zem-card p-4">
        <h2 class="font-display text-xl font-bold">Busiest {{ $restaurant->locationLabel(true) }}</h2>
        <div class="mt-4 grid gap-3">
            @forelse($busiestTables as $table)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-md bg-zem-bg px-3 py-2 text-sm">
                    <strong>{{ $placeTitle }} {{ $table->table_number }}</strong>
                    <span class="text-zem-muted">{{ number_format($table->orders_count) }} orders - {{ number_format($table->revenue_total) }} ETB</span>
                </div>
            @empty
                <p class="text-sm text-zem-muted">No {{ $restaurant->locationLabel() }} activity in the last 30 days yet.</p>
            @endforelse
        </div>
    </section>
</div>

<section class="mt-6 rounded-md border border-zem-border bg-zem-card p-4">
    <h2 class="font-display text-xl font-bold">Daily trend - last 30 days</h2>
    <div class="mt-4 grid gap-2">
        @forelse($dailyTrends as $day)
            <div class="grid gap-2 rounded-md bg-zem-bg px-3 py-2 text-sm md:grid-cols-3">
                <strong>{{ \Carbon\Carbon::parse($day->order_date)->format('M j, Y') }}</strong>
                <span class="text-zem-muted">{{ number_format($day->orders_count) }} orders</span>
                <span class="text-zem-muted">{{ number_format($day->revenue_total) }} ETB</span>
            </div>
        @empty
            <p class="text-sm text-zem-muted">No order trend data yet.</p>
        @endforelse
    </div>
</section>
@endsection
