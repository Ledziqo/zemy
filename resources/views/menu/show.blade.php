@extends('layouts.app', [
    'title' => $restaurant->name.' Menu - '.$restaurant->locationLabelTitle().' '.$table->table_number.' | ZemTab',
    'description' => 'View the digital QR menu for '.$restaurant->name.'. '.$restaurant->locationLabelTitle().' '.$table->table_number.'.',
    'keywords' => $restaurant->name.' menu, QR menu, '.$restaurant->locationLabel().' ordering, ZemTab',
    'canonical' => route('menu.show', [$restaurant->slug, $table->table_number]),
    'ogType' => 'website',
    'ogImage' => $restaurant->cover_image_path ? asset($restaurant->cover_image_path) : asset('logo/zemtab-full-transparent.png'),
    'robots' => 'index, follow',
])

@section('content')
@php
    $settings = $restaurant->settings ?? [];
    $enabledPaymentMethods = $settings['payment_methods'] ?? ['cash', 'telebirr', 'cbe'];
    $paymentLabels = ['cash' => 'Cash', 'telebirr' => 'Telebirr', 'cbe' => 'CBE'];
    $paymentDetails = [
        'cash' => 'Pay with cash when staff brings your order or bill.',
        'telebirr' => filled($settings['telebirr_number'] ?? null) ? 'Send Telebirr payment to '.$settings['telebirr_number'].'.' : 'Ask staff for the Telebirr number.',
        'cbe' => filled($settings['cbe_account_number'] ?? null) ? 'Transfer to CBE account '.$settings['cbe_account_number'].'.' : 'Ask staff for the CBE account number.',
    ];
    $logoUrl = $restaurant->logo_path ? (\Illuminate\Support\Str::startsWith($restaurant->logo_path, ['http://', 'https://', 'uploads/']) ? (str_starts_with($restaurant->logo_path, 'uploads/') ? asset($restaurant->logo_path) : $restaurant->logo_path) : asset('storage/'.$restaurant->logo_path)) : null;
    $placeTitle = $restaurant->locationLabelTitle();
@endphp
<main x-data="menuCart({ paymentDetails: @js($paymentDetails) })" class="min-h-screen bg-neutral-100 pb-28 text-zem-ink">
    <header class="sticky top-0 z-30 border-b border-black/10 bg-white/95 px-4 py-3 shadow-sm backdrop-blur">
        <div class="mx-auto max-w-5xl">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $restaurant->name }} logo" class="h-14 w-14 shrink-0 rounded-xl border border-black/10 bg-white object-contain p-1">
                    @else
                        <div class="grid h-14 w-14 shrink-0 place-items-center rounded-xl bg-black text-xl font-extrabold text-white">{{ strtoupper(substr($restaurant->name, 0, 1)) }}</div>
                    @endif
                    <div class="min-w-0">
                        <p class="text-xs font-extrabold uppercase tracking-widest text-zem-gold">{{ $placeTitle }} {{ $table->table_number }}</p>
                        <h1 class="truncate font-display text-2xl font-extrabold">{{ $restaurant->name }}</h1>
                        <p class="truncate text-sm text-neutral-500">{{ $restaurant->location ?: 'Digital menu' }}</p>
                    </div>
                </div>
                <button type="button" @click="open = true" class="rounded-xl bg-black px-4 py-3 text-sm font-extrabold text-white">
                    Cart <span x-text="count()"></span>
                </button>
            </div>
            <nav class="mt-3 flex gap-2 overflow-x-auto pb-1">
                <a href="#all" class="whitespace-nowrap rounded-full bg-zem-gold px-4 py-2 text-sm font-extrabold text-white">All</a>
                @foreach($categories as $category)
                    <a href="#cat-{{ $category->id }}" class="whitespace-nowrap rounded-full border border-black/10 bg-white px-4 py-2 text-sm font-extrabold text-neutral-700">{{ $category->name }}</a>
                @endforeach
            </nav>
        </div>
    </header>

    <section class="mx-auto max-w-5xl px-4 py-4">
        @if(session('success'))<div class="mb-4 rounded-xl bg-zem-green px-4 py-3 text-sm font-bold text-white">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="mb-4 rounded-xl bg-red-600 px-4 py-3 text-sm font-bold text-white">{{ $errors->first() }}</div>@endif
        <div class="grid grid-cols-2 gap-3">
            <form method="post" action="{{ route('service-requests.store', [$restaurant->slug, $table->table_number]) }}">@csrf<input type="hidden" name="type" value="call_waiter"><button class="w-full rounded-xl bg-black px-4 py-3 font-extrabold text-white">{{ $restaurant->staffRequestLabel() }}</button></form>
            <form method="post" action="{{ route('service-requests.store', [$restaurant->slug, $table->table_number]) }}">@csrf<input type="hidden" name="type" value="request_bill"><button class="w-full rounded-xl bg-zem-gold px-4 py-3 font-extrabold text-white">{{ $restaurant->isHotel() ? 'Request Room Bill' : 'Request Bill' }}</button></form>
        </div>
    </section>

    <section id="all" class="mx-auto max-w-5xl space-y-8 px-4">
        @foreach($categories as $category)
            <section id="cat-{{ $category->id }}" class="scroll-mt-28">
                <h2 class="mb-3 font-display text-2xl font-extrabold">{{ $category->name }}</h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($category->menuItems as $item)
                        @php($imageUrl = $item->image_path ? (\Illuminate\Support\Str::startsWith($item->image_path, ['http://', 'https://', 'uploads/']) ? (str_starts_with($item->image_path, 'uploads/') ? asset($item->image_path) : $item->image_path) : asset('storage/'.$item->image_path)) : null)
                        <article class="overflow-hidden rounded-2xl border border-black/10 bg-white shadow-sm" itemscope itemtype="https://schema.org/MenuItem">
                            <div class="aspect-square bg-neutral-200">
                                @if($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="{{ $item->name }}" class="h-full w-full object-cover">
                                @else
                                    <div class="grid h-full place-items-center bg-[linear-gradient(135deg,#111,#ef233c)] text-5xl font-extrabold text-white">{{ strtoupper(substr($item->name, 0, 1)) }}</div>
                                @endif
                            </div>
                            <div class="p-3">
                                <div class="min-h-20">
                                    <h3 class="line-clamp-2 font-extrabold leading-tight" itemprop="name">{{ $item->name }}</h3>
                                    <p class="mt-1 line-clamp-2 text-xs text-neutral-500" itemprop="description">{{ $item->description }}</p>
                                </div>
                                <p class="mt-2 font-extrabold text-zem-gold" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                                    <span itemprop="price" content="{{ $item->price }}">{{ number_format($item->price) }}</span>
                                    <span itemprop="priceCurrency" content="ETB">ETB</span>
                                </p>
                                @if($item->is_available)
                                    <button type="button" @click="add({ id: {{ $item->id }}, name: @js($item->name), price: {{ $item->price }} })" class="mt-3 w-full rounded-xl bg-black px-3 py-3 text-sm font-extrabold text-white">Add</button>
                                @else
                                    <span class="mt-3 block rounded-xl bg-red-100 px-3 py-3 text-center text-sm font-extrabold text-red-700">Unavailable</span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    </section>

    <button type="button" @click="open = true" x-show="count() > 0" class="fixed bottom-4 left-1/2 z-40 flex w-[calc(100%-2rem)] max-w-3xl -translate-x-1/2 items-center justify-between rounded-2xl bg-black px-5 py-4 font-extrabold text-white shadow-2xl shadow-black/40">
        <span x-text="count() + ' item(s)'"></span><span class="text-zem-gold" x-text="money(total())"></span>
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm" @click.self="open=false">
        <form method="post" action="{{ route('orders.store', [$restaurant->slug, $table->table_number]) }}" @submit="syncForm" class="absolute bottom-0 max-h-[92vh] w-full overflow-y-auto rounded-t-3xl bg-white p-4 text-zem-ink shadow-2xl">
            @csrf
            <div class="mx-auto max-w-3xl">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-2xl font-extrabold">Checkout</h2>
                    <button type="button" @click="open=false" class="rounded-lg border border-black/10 px-3 py-2 font-bold">Close</button>
                </div>

                <template x-if="items.length === 0"><p class="mt-5 rounded-xl bg-neutral-100 p-4 text-sm text-neutral-500">Your cart is empty.</p></template>
                <template x-for="item in items" :key="item.id">
                    <div class="mt-4 rounded-2xl border border-black/10 bg-neutral-50 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <div><p class="font-extrabold" x-text="item.name"></p><p class="text-sm text-neutral-500" x-text="money(item.price)"></p></div>
                            <div class="flex items-center gap-2"><button type="button" @click="dec(item.id)" class="h-10 w-10 rounded-lg border border-black/10 font-bold">-</button><span class="w-7 text-center font-bold" x-text="item.quantity"></span><button type="button" @click="add(item)" class="h-10 w-10 rounded-lg border border-black/10 font-bold">+</button></div>
                        </div>
                        <input x-model="item.note" placeholder="Special note for this item" class="mt-3 w-full rounded-lg border border-black/10 px-3 py-3 text-sm outline-none focus:border-zem-gold">
                    </div>
                </template>

                <div class="mt-5 grid gap-3">
                    <textarea name="note" rows="3" placeholder="Order note" class="rounded-lg border border-black/10 px-3 py-3 outline-none focus:border-zem-gold"></textarea>
                    <select name="payment_method" x-model="paymentMethod" class="rounded-lg border border-black/10 px-3 py-3 outline-none focus:border-zem-gold">
                        <option value="">Choose payment method later</option>
                        @foreach($enabledPaymentMethods as $method)
                            <option value="{{ $method }}">{{ $paymentLabels[$method] ?? ucfirst($method) }}</option>
                        @endforeach
                    </select>
                    <div x-show="paymentMethod" class="rounded-xl border border-zem-gold/30 bg-zem-gold/10 p-4 text-sm font-semibold text-neutral-800">
                        <p x-text="paymentDetails[paymentMethod] || 'Staff will confirm payment details.'"></p>
                    </div>
                    <div id="cart-fields"></div>
                    <div class="flex items-center justify-between text-lg font-extrabold"><span>Total</span><span x-text="money(total())"></span></div>
                    <button class="rounded-xl bg-zem-gold py-4 font-extrabold text-white" :disabled="items.length === 0">Place order</button>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
function menuCart(config) {
    return {
        open: false,
        items: [],
        paymentMethod: '',
        paymentDetails: config.paymentDetails || {},
        add(item) { const existing = this.items.find(i => i.id === item.id); existing ? existing.quantity++ : this.items.push({...item, quantity: 1, note: ''}); },
        dec(id) { const item = this.items.find(i => i.id === id); if (!item) return; item.quantity--; if (item.quantity <= 0) this.items = this.items.filter(i => i.id !== id); },
        count() { return this.items.reduce((sum, item) => sum + item.quantity, 0); },
        total() { return this.items.reduce((sum, item) => sum + item.quantity * item.price, 0); },
        money(value) { return new Intl.NumberFormat('en-US').format(value) + ' ETB'; },
        syncForm() {
            const holder = document.getElementById('cart-fields');
            holder.innerHTML = '';
            this.items.forEach((item, index) => {
                [['id', item.id], ['quantity', item.quantity], ['note', item.note || '']].forEach(([field, value]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `items[${index}][${field}]`;
                    input.value = value;
                    holder.appendChild(input);
                });
            });
        }
    }
}
</script>
@endsection
