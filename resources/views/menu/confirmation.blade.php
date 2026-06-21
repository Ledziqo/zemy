@extends('layouts.app', [
    'title' => 'Order Confirmed - '.$restaurant->name.' | ZemTab',
    'description' => 'Your order has been sent successfully to '.$restaurant->name.'. '.$restaurant->locationLabelTitle().' '.$table.'. Staff will be with you shortly.',
    'robots' => 'noindex, nofollow',
    'canonical' => route('menu.confirmation', [$restaurant->slug, $table]),
    'accentColor' => $restaurant->primary_color,
])

@section('content')
<main class="grid min-h-screen place-items-center bg-white px-5 text-zem-ink">
    <div class="max-w-md rounded-2xl border border-black/10 bg-white p-8 text-center shadow-2xl">
        <p class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-zem-gold text-3xl font-extrabold text-white">OK</p>
        <h1 class="mt-5 font-display text-3xl font-extrabold">Order sent.</h1>
        <p class="mt-3 text-neutral-500">You can reload the menu, add more items, or pay at the end of this visit.</p>
        @php($confirmedOrder = $visit?->orders?->firstWhere('id', session('order_id')))
        @if($confirmedOrder)
            @php($cancelDeadline = $confirmedOrder->created_at->copy()->addMinutes(2))
            <div class="mt-4 rounded-xl bg-neutral-50 p-4 text-left" x-data="cancelTimer(@js($cancelDeadline->toIso8601String()))" x-init="start()">
                <p class="font-bold">Order #{{ $confirmedOrder->id }}</p>
                <ul class="mt-2 space-y-1 text-sm text-neutral-600">
                    @foreach($confirmedOrder->items as $item)<li>{{ $item->quantity }} &times; {{ $item->item_name }}</li>@endforeach
                </ul>
                @if($confirmedOrder->status === 'new' && $cancelDeadline->isFuture())
                    <form method="post" action="{{ route('orders.cancel', [$restaurant->slug, $table, $confirmedOrder]) }}" class="mt-3" x-show="remaining > 0" onsubmit="return confirm('Cancel this order?')">
                        @csrf @method('PATCH')
                        <button class="w-full rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-center font-bold text-red-700">Cancel order &middot; <span x-text="clock"></span></button>
                    </form>
                    <p x-show="remaining <= 0" x-cloak class="mt-3 text-center text-xs font-bold text-neutral-500">Cancellation window ended</p>
                @endif
            </div>
        @endif
        <a href="{{ route('menu.show', [$restaurant->slug, $table]) }}" class="mt-6 inline-flex rounded-lg bg-black px-5 py-3 font-bold text-white">Back to menu</a>
    </div>
</main>
<script>
function cancelTimer(deadline) {
    return {
        remaining: Math.max(0, Math.ceil((new Date(deadline).getTime() - Date.now()) / 1000)),
        timer: null,
        get clock() { return Math.floor(this.remaining / 60) + ':' + String(this.remaining % 60).padStart(2, '0'); },
        start() {
            this.timer = setInterval(() => {
                this.remaining = Math.max(0, Math.ceil((new Date(deadline).getTime() - Date.now()) / 1000));
                if (this.remaining <= 0) clearInterval(this.timer);
            }, 250);
        }
    };
}
</script>
@endsection
