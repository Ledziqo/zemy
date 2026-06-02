@extends('layouts.app', [
    'title' => 'Order Confirmed — '.$restaurant->name.' | ZemTab',
    'description' => 'Your order has been sent successfully to '.$restaurant->name.'. Table '.$table.'. Your waiter will be with you shortly.',
    'robots' => 'noindex, nofollow',
    'canonical' => route('menu.confirmation', [$restaurant->slug, $table]),
])

@section('content')
<main class="grid min-h-screen place-items-center bg-white px-5 text-zem-ink">
    <div class="max-w-md rounded-2xl border border-black/10 bg-white p-8 text-center shadow-2xl">
        <p class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-zem-gold text-3xl font-extrabold text-white">✓</p>
        <h1 class="mt-5 font-display text-3xl font-extrabold">Order sent.</h1>
        <p class="mt-3 text-neutral-500">Your waiter will be with you shortly.</p>
        @if(session('order_id'))<p class="mt-3 text-sm font-bold">Order #{{ session('order_id') }}</p>@endif
        <a href="{{ route('menu.show', [$restaurant->slug, $table]) }}" class="mt-6 inline-flex rounded-lg bg-black px-5 py-3 font-bold text-white">Back to menu</a>
    </div>
</main>
@endsection
