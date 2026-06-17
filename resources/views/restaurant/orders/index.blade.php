@extends('layouts.dashboard', ['heading' => 'Work Board', 'eyebrow' => 'Live orders & service requests', 'autoRefreshSeconds' => 5])

@section('content')
@include('restaurant.partials.order_sound_alerts', ['latestOrderId' => $latestOrderId])
@php($placeTitle = $restaurant->locationLabelTitle())
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-md border border-zem-border bg-zem-card px-4 py-3"><p class="text-xs text-zem-muted">Visible orders</p><p class="mt-1 text-2xl font-extrabold">{{ $orders->total() }}</p></div>
        <div class="rounded-md border border-zem-border bg-zem-card px-4 py-3"><p class="text-xs text-zem-muted">Active requests</p><p class="mt-1 text-2xl font-extrabold">{{ $activeRequests }}</p></div>
        <div class="rounded-md border border-zem-border bg-zem-card px-4 py-3"><p class="text-xs text-zem-muted">Updated</p><p class="mt-1 text-sm font-bold">{{ now()->format('H:i:s') }}</p></div>
    </div>
    <a href="{{ route('restaurant.orders.index') }}" class="rounded-md border border-zem-border px-4 py-2 text-sm font-bold">Refresh</a>
</div>

<div class="grid gap-5 xl:grid-cols-[minmax(0,1.5fr)_minmax(360px,.9fr)]">
    <section>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-display text-xl font-bold">Orders</h2>
            <span class="text-sm text-zem-muted">{{ $placeTitle }} orders</span>
        </div>
        <div class="grid gap-4">
        @forelse($orders as $order)
            <article class="rounded-md border border-zem-border bg-zem-card p-4">
                <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-display text-xl font-bold">Order #{{ $order->id }}</h2><p class="text-sm text-zem-muted">{{ $placeTitle }} {{ $order->table_number }} - {{ $order->created_at->diffForHumans() }}</p></div><x-status :status="$order->status" /></div>
                <div class="mt-4 space-y-2">
                    @foreach($order->items as $item)<p class="flex justify-between gap-3 rounded-md border border-zem-border bg-zem-bg px-3 py-2 text-sm text-zem-cream"><span>{{ $item->quantity }} x {{ $item->item_name }} @if($item->note)<em class="text-zem-muted">({{ $item->note }})</em>@endif</span><strong class="shrink-0 text-zem-cream">{{ number_format($item->total_price) }} ETB</strong></p>@endforeach
                </div>
                <p class="mt-3 text-sm text-zem-muted">Note: {{ $order->note ?: 'None' }}</p>
                @if($order->guestSession?->payments?->isNotEmpty())
                    <div class="mt-3 rounded-md border border-zem-gold/30 bg-zem-gold/10 p-3">
                        <p class="text-sm font-bold text-zem-gold">Payment proof for this visit</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($order->guestSession->payments as $payment)
                                <a href="{{ asset($payment->proof_image_path) }}" target="_blank" class="flex items-center gap-3 rounded-md border border-zem-border bg-zem-bg p-2 text-sm font-bold text-zem-cream">
                                    <img src="{{ asset($payment->proof_image_path) }}" alt="Payment proof" class="h-14 w-14 rounded object-cover">
                                    <span>{{ strtoupper($payment->method) }} - {{ number_format($payment->amount) }} ETB - {{ $payment->status }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3"><strong>{{ number_format($order->total) }} ETB</strong><form method="post" action="{{ route('restaurant.orders.update', $order) }}" class="flex gap-2">@csrf @method('PATCH')<select name="status" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2 text-zem-cream">@foreach($statuses as $status)<option value="{{ $status }}" @selected($order->status===$status)>{{ $status }}</option>@endforeach</select><button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Update</button></form></div>
            </article>
        @empty
            <p class="rounded-md border border-zem-border bg-zem-card p-4 text-zem-muted">No orders yet.</p>
        @endforelse
        </div>
        <div class="mt-5">{{ $orders->links() }}</div>
    </section>

    <aside>
        <div class="sticky top-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-display text-xl font-bold">Service requests</h2>
                <span class="text-sm text-zem-muted">Pending first</span>
            </div>
            <div class="grid gap-3">
                @forelse($requests as $requestRow)
                    <form method="post" action="{{ route('restaurant.service-requests.update', $requestRow) }}" class="grid gap-3 rounded-md border {{ in_array($requestRow->status, ['pending', 'acknowledged'], true) ? 'border-zem-gold/40 bg-zem-gold/10' : 'border-zem-border bg-zem-card' }} p-4">
                        @csrf @method('PATCH')
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <strong>{{ $placeTitle }} {{ $requestRow->table_number }}</strong>
                                <p class="mt-1 text-sm text-zem-muted">{{ $restaurant->requestTypeLabel($requestRow->type) }} - {{ $requestRow->created_at->diffForHumans() }}</p>
                                @if($requestRow->note)<p class="mt-2 text-sm">{{ $requestRow->note }}</p>@endif
                            </div>
                            <x-status :status="$requestRow->status" />
                        </div>
                        <div class="flex gap-2">
                            <select name="status" class="min-w-0 flex-1 rounded-md border border-zem-border bg-zem-bg px-3 py-3 text-zem-cream">
                                @foreach(['pending','acknowledged','completed'] as $status)
                                    <option value="{{ $status }}" @selected($requestRow->status===$status)>{{ $status }}</option>
                                @endforeach
                            </select>
                            <button class="rounded-md bg-zem-gold px-4 py-3 font-bold text-white">Update</button>
                        </div>
                    </form>
                @empty
                    <p class="rounded-md border border-zem-border bg-zem-card p-4 text-zem-muted">No service requests yet.</p>
                @endforelse
            </div>
        </div>
    </aside>
</div>
@endsection
