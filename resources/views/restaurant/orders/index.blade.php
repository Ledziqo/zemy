@extends('layouts.dashboard', ['heading' => 'Orders', 'eyebrow' => 'Live order board', 'autoRefreshSeconds' => 5])

@section('content')
@include('restaurant.partials.order_sound_alerts', ['latestOrderId' => $latestOrderId])
@php($placeTitle = $restaurant->locationLabelTitle())
<div class="mb-4 flex justify-end"><a href="{{ route('restaurant.orders.index') }}" class="rounded-md border border-zem-border px-4 py-2 text-sm font-bold">Refresh</a></div>
<div class="grid gap-4 xl:grid-cols-2">
@foreach($orders as $order)
    <article class="rounded-md border border-zem-border bg-zem-card p-4">
        <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-display text-xl font-bold">Order #{{ $order->id }}</h2><p class="text-sm text-zem-muted">{{ $placeTitle }} {{ $order->table_number }} - {{ $order->created_at->diffForHumans() }}</p></div><x-status :status="$order->status" /></div>
        <div class="mt-4 space-y-2">
            @foreach($order->items as $item)<p class="flex justify-between rounded-md bg-zem-bg px-3 py-2 text-sm"><span>{{ $item->quantity }} x {{ $item->item_name }} @if($item->note)<em class="text-zem-muted">({{ $item->note }})</em>@endif</span><strong>{{ number_format($item->total_price) }} ETB</strong></p>@endforeach
        </div>
        <p class="mt-3 text-sm text-zem-muted">Note: {{ $order->note ?: 'None' }} - Payment: {{ str_replace('_', ' ', $order->payment_method ?: 'not selected') }}</p>
        @if($order->guestSession?->payments?->isNotEmpty())
            <div class="mt-3 rounded-md border border-zem-gold/30 bg-zem-gold/10 p-3">
                <p class="text-sm font-bold text-zem-gold">Payment proof for this visit</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach($order->guestSession->payments as $payment)
                        <a href="{{ asset($payment->proof_image_path) }}" target="_blank" class="flex items-center gap-3 rounded-md border border-zem-border bg-zem-bg p-2 text-sm font-bold text-white">
                            <img src="{{ asset($payment->proof_image_path) }}" alt="Payment proof" class="h-14 w-14 rounded object-cover">
                            <span>{{ strtoupper($payment->method) }} - {{ number_format($payment->amount) }} ETB - {{ $payment->status }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3"><strong>{{ number_format($order->total) }} ETB</strong><form method="post" action="{{ route('restaurant.orders.update', $order) }}" class="flex gap-2">@csrf @method('PATCH')<select name="status" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">@foreach($statuses as $status)<option value="{{ $status }}" @selected($order->status===$status)>{{ $status }}</option>@endforeach</select><button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Update</button></form></div>
    </article>
@endforeach
</div>
<div class="mt-5">{{ $orders->links() }}</div>
@endsection
