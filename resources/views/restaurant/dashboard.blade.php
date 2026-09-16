@extends('layouts.dashboard', ['heading' => __('Overview'), 'eyebrow' => $restaurant->name])

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div><h2 class="text-lg font-semibold">{{ __('Your day at a glance') }}</h2><p class="mt-1 text-sm text-zem-muted">{{ now()->format('l, j F') }} · {{ __('Orders, service and sales in one place.') }}</p></div>
    <a href="{{ route('restaurant.orders.index') }}" class="inline-flex items-center gap-3 rounded-xl bg-zem-gold px-5 py-3 text-sm font-semibold text-white hover:bg-zem-redDark">{{ __('Open work board') }} <span aria-hidden="true">→</span></a>
</div>
<div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
    @foreach([[__('Today orders'), number_format($todayOrders), __('Placed today')], [__('New'), number_format($newOrders), __('Today’s new orders')], [__('Preparing'), number_format($preparingOrders), __('In preparation today')], [__('Revenue today'), number_format($revenue, 2).' ETB', __('Paid and completed orders')]] as [$label, $value, $description])
        <div class="rounded-2xl border border-zem-border bg-zem-card p-4 md:p-5">
            <p class="text-sm text-zem-muted">{{ $label }}</p><p class="metric-value mt-3 break-words text-2xl font-semibold">{{ $value }}</p><p class="mt-2 text-xs text-zem-muted">{{ $description }}</p>
        </div>
    @endforeach
</div>
<div class="mt-6 grid items-start gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(260px,1fr)]">
    <section class="overflow-hidden border border-zem-border bg-zem-card">
        <div class="flex items-center justify-between gap-3 border-b border-zem-border px-5 py-4"><h2 class="font-semibold">{{ __('Recent orders') }}</h2><a class="text-sm font-semibold text-zem-gold" href="{{ route('restaurant.orders.index', ['filter' => 'all']) }}">{{ __('View all') }} →</a></div>
        <div class="divide-y divide-zem-border">
            @forelse($recentOrders as $order)
                <details class="group px-5 py-4">
                    <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0"><p class="text-sm font-semibold">#{{ $order->id }} <span class="mx-1 text-zem-muted">·</span> {{ $order->table?->displayLabel() ?: $order->table_number }}</p><p class="mt-1 text-xs text-zem-muted">{{ $order->items->sum('quantity') }} {{ __('items') }} · {{ $order->created_at->diffForHumans() }} <span class="ml-2 group-open:hidden">{{ __('Details') }} ↓</span></p></div>
                        <div class="flex items-center gap-3"><span class="text-sm font-semibold">{{ number_format($order->total, 2) }} ETB</span><x-status :status="$order->status" /></div>
                    </summary>
                    <div class="mt-4 space-y-2 border-t border-zem-border pt-3">
                        @foreach($order->items as $item)<p class="flex justify-between gap-3 text-sm"><span>{{ $item->quantity }} × {{ $item->item_name }} @if($item->note)<span class="text-zem-muted">({{ $item->note }})</span>@endif</span><span class="shrink-0 text-zem-muted">{{ number_format($item->total_price, 2) }} ETB</span></p>@endforeach
                    </div>
                </details>
            @empty
                <div class="px-6 py-12 text-center"><h3 class="font-semibold">{{ __('Ready for your first order') }}</h3><p class="mx-auto mt-2 max-w-sm text-sm text-zem-muted">{{ __('Share a table QR code with your guests. New orders will appear on your work board.') }}</p><a href="{{ route('restaurant.tables.index') }}" class="mt-4 inline-block text-sm font-semibold text-zem-gold">{{ __('View QR codes') }} →</a></div>
            @endforelse
        </div>
    </section>
    <div class="space-y-6">
        <section class="border border-zem-border bg-zem-card p-5">
            <h2 class="font-semibold">{{ __('Quick access') }}</h2>
            @foreach([[__('Menu Items'), __('Update prices and availability'), 'restaurant.menu-items.index'], [$restaurant->locationLabelTitle(true).' / QR', __('Manage guest access'), 'restaurant.tables.index'], [__('Analytics'), __('See sales and order trends'), 'restaurant.analytics']] as [$label, $description, $destination])
                <a href="{{ route($destination) }}" class="mt-3 flex items-center justify-between gap-3 rounded-xl px-3 py-3 transition hover:bg-zem-soft"><span><span class="block text-sm font-semibold">{{ $label }}</span><span class="mt-1 block text-xs text-zem-muted">{{ $description }}</span></span><span aria-hidden="true" class="text-zem-muted">→</span></a>
            @endforeach
        </section>
        @if($popularItems->isNotEmpty())
            <section class="border border-zem-border bg-zem-card p-5"><h2 class="font-semibold">{{ __('Popular items this week') }}</h2><div class="mt-4 space-y-4">@foreach($popularItems as $item)<div class="flex items-center justify-between gap-3 text-sm"><span>{{ $item->item_name }}</span><span class="shrink-0 text-zem-muted">{{ number_format($item->quantity_sold) }} {{ __('sold') }}</span></div>@endforeach</div></section>
        @endif
    </div>
</div>
@endsection
