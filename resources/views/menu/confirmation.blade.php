@extends('layouts.app', ['title' => 'Your order | '.$restaurant->name, 'robots' => 'noindex, nofollow', 'accentColor' => $restaurant->primary_color, 'alpine' => true])
@section('content')
<script src="{{ asset('assets/guest-experience.js') }}?v={{ filemtime(public_path('assets/guest-experience.js')) }}"></script>
@php
    $statusLabels = [
        'new' => [__('Sent to staff'), __('Your order is waiting for staff to start preparing it.')],
        'preparing' => [__('Being prepared'), __('The team is preparing your order.')],
        'served' => [__('Served'), __('Enjoy your meal. Ask staff when you are ready for the bill.')],
        'paid' => [__('Paid'), __('Payment has been recorded. Thank you for visiting.')],
        'completed' => [__('Completed'), __('Thank you for visiting.')],
        'cancelled' => [__('Cancelled'), __('This order has been cancelled. Please ask staff if you need help.')],
    ];
@endphp
<main class="guest-confirmation min-h-screen bg-white px-5 py-8 text-zem-ink">
    <div class="mx-auto w-full max-w-md rounded-2xl border border-black/10 bg-white p-6 shadow-xl">
        <p class="text-sm font-bold">{{ $restaurant->name }}</p>
        <h1 class="mt-3 text-3xl font-bold">{{ $order ? __('Order received') : __('Your order') }}</h1>
        @if($order)
            <p class="mt-2 font-bold">{{ __('Order') }} #{{ $order->id }}</p>
            <section class="guest-progress">
                <div><h2>{{ $statusLabels[$order->status][0] ?? $order->status }}</h2><p>{{ $statusLabels[$order->status][1] ?? '' }}</p></div>
            </section>
            <ul class="guest-receipt">
                @foreach($order->items as $item)
                    <li><div><strong>{{ $item->quantity }} × {{ $item->item_name }}</strong>@if($item->note)<p>{{ $item->note }}</p>@endif</div><span>{{ number_format($item->total_price, 2) }} ETB</span></li>
                @endforeach
            </ul>
            @if($order->note)<p class="my-3 text-sm">{{ __('Order note') }}: {{ $order->note }}</p>@endif
            <dl class="guest-receipt-totals">
                <div><dt>{{ __('Items total') }}</dt><dd>{{ number_format($order->subtotal, 2) }} ETB</dd></div>
                @if((float) $order->service_charge > 0)<div><dt>{{ __('Service charge') }}</dt><dd>{{ number_format($order->service_charge, 2) }} ETB</dd></div>@endif
                @if((float) $order->tax > 0)<div><dt>{{ __('VAT') }}</dt><dd>{{ number_format($order->tax, 2) }} ETB</dd></div>@endif
                <div><dt>{{ __('Final total') }}</dt><dd>{{ number_format($order->total, 2) }} ETB</dd></div>
            </dl>
        @else
            <p class="mt-4">{{ __('Return to the menu to browse or place an order.') }}</p>
        @endif
        <a href="{{ route('menu.show', [$restaurant->slug, $table]) }}" class="mt-6 inline-flex rounded-lg bg-black px-5 py-3 font-bold text-white">{{ __('Back to menu') }}</a>
    </div>
</main>
@if($order && session('order_id') == $order->id)
<script>try { localStorage.removeItem(@js('zemtab-cart:'.$restaurant->id.':'.$table)); } catch (_) {}</script>
@endif
@endsection
