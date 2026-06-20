@extends('layouts.dashboard', ['heading' => 'Analytics', 'eyebrow' => $restaurant->businessTypeLabel().' Growth'])

@section('content')
@php($placeTitle = $restaurant->locationLabelTitle())
<div class="grid gap-4 md:grid-cols-3 xl:grid-cols-5">
    @foreach([['Today orders',$todayOrders],['Today revenue',number_format($todayRevenue).' ETB'],['30-day orders',$last30Orders],['30-day revenue',number_format($last30Revenue).' ETB'],['Completed',$completedOrders]] as $card)
        <div class="rounded-md border border-zem-border bg-zem-card p-4"><p class="text-sm text-zem-muted">{{ $card[0] }}</p><p class="mt-2 text-2xl font-extrabold">{{ $card[1] }}</p></div>
    @endforeach
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <section class="rounded-md border border-zem-border bg-zem-card p-4">
        <h2 class="font-display text-xl font-bold">Top-selling items</h2>
        <div class="mt-4 grid gap-3">
            @forelse($topItems as $item)
                @php($maxQty = $topItems->first()->quantity_sold ?? 1)
                @php($pct = round(($item->quantity_sold / $maxQty) * 100))
                <div>
                    <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                        <strong>{{ $item->item_name }}</strong>
                        <span class="text-zem-muted">{{ number_format($item->quantity_sold) }} sold - {{ number_format($item->revenue_total) }} ETB</span>
                    </div>
                    <div class="mt-1 h-2 rounded-full bg-zem-bg"><div class="h-2 rounded-full bg-zem-gold" style="width: {{ $pct }}%"></div></div>
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
                @php($maxOrders = $busiestTables->first()->orders_count ?? 1)
                @php($pct = round(($table->orders_count / $maxOrders) * 100))
                <div>
                    <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                        <strong>{{ $placeTitle }} {{ $table->table_number }}</strong>
                        <span class="text-zem-muted">{{ number_format($table->orders_count) }} orders - {{ number_format($table->revenue_total) }} ETB</span>
                    </div>
                    <div class="mt-1 h-2 rounded-full bg-zem-bg"><div class="h-2 rounded-full bg-zem-gold" style="width: {{ $pct }}%"></div></div>
                </div>
            @empty
                <p class="text-sm text-zem-muted">No {{ $restaurant->locationLabel() }} activity in the last 30 days yet.</p>
            @endforelse
        </div>
    </section>
</div>

<section class="mt-6 rounded-md border border-zem-border bg-zem-card p-4">
    <h2 class="font-display text-xl font-bold">Daily trend - last 30 days</h2>
    @php($maxDayOrders = $dailyTrends->max('orders_count') ?? 1)
    <div class="mt-4 flex items-end gap-1 overflow-x-auto pb-2" style="min-height: 120px;">
        @forelse($dailyTrends as $day)
            @php($heightPct = $maxDayOrders > 0 ? max(4, round(($day->orders_count / $maxDayOrders) * 100)) : 4)
            <div class="group flex shrink-0 flex-col items-center gap-1" style="width: 28px;">
                <div class="w-full rounded-t bg-zem-gold/80 transition group-hover:bg-zem-gold" style="height: {{ $heightPct }}px;" title="{{ $day->order_date }}: {{ $day->orders_count }} orders, {{ number_format($day->revenue_total) }} ETB"></div>
                <span class="text-[9px] text-zem-muted rotate-45 origin-left whitespace-nowrap">{{ \Carbon\Carbon::parse($day->order_date)->format('M j') }}</span>
            </div>
        @empty
            <p class="text-sm text-zem-muted">No order trend data yet.</p>
        @endforelse
    </div>
</section>
@endsection
