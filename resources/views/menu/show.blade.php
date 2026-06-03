@extends('layouts.app', [
    'title' => $restaurant->name.' Menu — Table '.$table->table_number.' | ZemTab',
    'description' => 'View the digital QR menu for '.$restaurant->name.' in '.$restaurant->location.'. Table '.$table->table_number.'. Order directly from your phone with ZemTab — no app download needed.',
    'keywords' => $restaurant->name.' menu, '.$restaurant->location.' restaurant, QR menu, table ordering, digital menu Ethiopia, ZemTab',
    'canonical' => route('menu.show', [$restaurant->slug, $table->table_number]),
    'ogType' => 'website',
    'ogImage' => $restaurant->cover_image_path ? asset($restaurant->cover_image_path) : asset('logo/zemtab-full-transparent.png'),
    'robots' => 'index, follow',
])

@section('content')
@php
    $enabledPaymentMethods = $restaurant->settings['payment_methods'] ?? ['cash', 'telebirr', 'cbe'];
    $paymentLabels = ['cash' => 'Cash', 'telebirr' => 'Telebirr', 'cbe' => 'CBE'];
@endphp
<main x-data="menuCart()" class="min-h-screen bg-white pb-32 text-zem-ink">

    {{-- Breadcrumb Navigation (Hidden visually but available to screen readers and structured data) --}}
    <nav aria-label="Breadcrumb" class="sr-only">
        <ol itemscope itemtype="https://schema.org/BreadcrumbList">
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a itemprop="item" href="{{ url('/') }}"><span itemprop="name">ZemTab</span></a>
                <meta itemprop="position" content="1" />
            </li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a itemprop="item" href="{{ route('menu.show', [$restaurant->slug, $table->table_number]) }}"><span itemprop="name">{{ $restaurant->name }} Menu</span></a>
                <meta itemprop="position" content="2" />
            </li>
        </ol>
    </nav>

    <header class="sticky top-0 z-30 border-b border-black/10 bg-white/90 px-4 py-4 backdrop-blur-xl">
        <div class="mx-auto max-w-3xl">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-widest text-zem-gold">ZemTab Menu</p>
                    <h1 class="font-display text-3xl font-extrabold">{{ $restaurant->name }}</h1>
                    <p class="text-sm text-neutral-500">{{ $restaurant->location }} - Table {{ $table->table_number }}</p>
                </div>
                <div class="rounded-xl bg-black px-4 py-3 text-center text-white shadow-xl">
                    <p class="text-xs text-neutral-400">Table</p>
                    <p class="text-xl font-extrabold">{{ $table->table_number }}</p>
                </div>
            </div>
            <div class="mt-4 flex gap-2 overflow-x-auto pb-1">
                <a href="#all" class="whitespace-nowrap rounded-full bg-zem-gold px-4 py-2 text-sm font-extrabold text-white">All</a>
                @foreach($categories as $category)
                    <a href="#cat-{{ $category->id }}" class="whitespace-nowrap rounded-full border border-black/10 bg-white px-4 py-2 text-sm font-extrabold text-neutral-600">{{ $category->name }}</a>
                @endforeach
            </div>
        </div>
    </header>

    <section class="mx-auto max-w-3xl px-4 py-4">
        @if(session('success'))<div class="mb-4 rounded-xl bg-zem-green px-4 py-3 text-sm font-bold text-white">{{ session('success') }}</div>@endif
        <div class="grid grid-cols-2 gap-3">
            <form method="post" action="{{ route('service-requests.store', [$restaurant->slug, $table->table_number]) }}">@csrf<input type="hidden" name="type" value="call_waiter"><button class="w-full rounded-xl border border-black/10 bg-black px-4 py-3 font-extrabold text-white">Call Waiter</button></form>
            <form method="post" action="{{ route('service-requests.store', [$restaurant->slug, $table->table_number]) }}">@csrf<input type="hidden" name="type" value="request_bill"><button class="w-full rounded-xl border border-zem-gold bg-zem-gold px-4 py-3 font-extrabold text-white">Request Bill</button></form>
        </div>
    </section>

    <section id="all" class="mx-auto max-w-3xl space-y-8 px-4">
        @foreach($categories as $category)
            <div id="cat-{{ $category->id }}">
                <h2 class="mb-3 font-display text-2xl font-extrabold">{{ $category->name }}</h2>
                <div class="space-y-3">
                    @foreach($category->menuItems as $item)
                        @php($imageUrl = $item->image_path ? (\Illuminate\Support\Str::startsWith($item->image_path, ['http://', 'https://']) ? $item->image_path : asset('storage/'.$item->image_path)) : null)
                        <article class="grid grid-cols-[88px_1fr] gap-3 rounded-2xl border border-black/10 bg-white p-3 shadow-sm" itemscope itemtype="https://schema.org/MenuItem">
                            @if($imageUrl)
                                <img src="{{ $imageUrl }}" alt="{{ $item->name }}" class="h-24 w-full rounded-xl object-cover">
                            @else
                                <div class="grid h-24 place-items-center rounded-xl bg-gradient-to-br from-black to-zem-gold text-2xl font-extrabold text-white" aria-hidden="true">{{ strtoupper(substr($item->name, 0, 1)) }}</div>
                            @endif
                            <div>
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h3 class="font-extrabold" itemprop="name">{{ $item->name }}</h3>
                                        <p class="mt-1 line-clamp-2 text-sm text-neutral-500" itemprop="description">{{ $item->description }}</p>
                                    </div>
                                    <p class="whitespace-nowrap font-extrabold text-zem-gold" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                                        <span itemprop="price" content="{{ $item->price }}">{{ number_format($item->price) }}</span>
                                        <span itemprop="priceCurrency" content="ETB">ETB</span>
                                    </p>
                                </div>
                                <div class="mt-3 flex items-center justify-between">
                                    @if($item->is_available)
                                        <span class="text-xs font-extrabold text-green-700" itemprop="availability" content="https://schema.org/InStock">Available</span>
                                        <button type="button" @click="add({ id: {{ $item->id }}, name: @js($item->name), price: {{ $item->price }} })" class="rounded-lg bg-black px-4 py-2 text-sm font-extrabold text-white">Add</button>
                                    @else
                                        <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-extrabold text-red-700" itemprop="availability" content="https://schema.org/OutOfStock">Unavailable</span>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endforeach
    </section>

    <button type="button" @click="open = true" x-show="count() > 0" class="fixed bottom-4 left-1/2 z-40 flex w-[calc(100%-2rem)] max-w-3xl -translate-x-1/2 items-center justify-between rounded-2xl bg-black px-5 py-4 font-extrabold text-white shadow-2xl shadow-black/40">
        <span x-text="count() + ' item(s) in cart'"></span><span class="text-zem-gold" x-text="money(total())"></span>
    </button>

    <div x-show="open" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm" @click.self="open=false">
        <form method="post" action="{{ route('orders.store', [$restaurant->slug, $table->table_number]) }}" @submit="syncForm" class="absolute bottom-0 max-h-[90vh] w-full overflow-y-auto rounded-t-3xl bg-white p-4 text-zem-ink shadow-2xl">
            @csrf
            <div class="mx-auto max-w-3xl">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-2xl font-extrabold">Your order</h2>
                    <button type="button" @click="open=false" class="rounded-lg border border-black/10 px-3 py-2 font-bold">Close</button>
                </div>
                <template x-for="item in items" :key="item.id">
                    <div class="mt-4 rounded-2xl border border-black/10 bg-neutral-50 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <div><p class="font-extrabold" x-text="item.name"></p><p class="text-sm text-neutral-500" x-text="money(item.price)"></p></div>
                            <div class="flex items-center gap-2"><button type="button" @click="dec(item.id)" class="h-9 w-9 rounded-lg border border-black/10 font-bold">-</button><span class="w-6 text-center font-bold" x-text="item.quantity"></span><button type="button" @click="add(item)" class="h-9 w-9 rounded-lg border border-black/10 font-bold">+</button></div>
                        </div>
                        <input x-model="item.note" placeholder="Special note for this item" class="mt-3 w-full rounded-lg border border-black/10 px-3 py-2 text-sm outline-none focus:border-zem-gold">
                    </div>
                </template>
                <div class="mt-5 grid gap-3">
                    <textarea name="note" rows="3" placeholder="Order note" class="rounded-lg border border-black/10 px-3 py-2 outline-none focus:border-zem-gold"></textarea>
                    <select name="payment_method" class="rounded-lg border border-black/10 px-3 py-3 outline-none focus:border-zem-gold">
                        <option value="">Choose payment method later</option>
                        @foreach($enabledPaymentMethods as $method)
                            <option value="{{ $method }}">{{ $paymentLabels[$method] ?? ucfirst($method) }}</option>
                        @endforeach
                    </select>
                    <div id="cart-fields"></div>
                    <div class="flex items-center justify-between text-lg font-extrabold"><span>Total</span><span x-text="money(total())"></span></div>
                    <button class="rounded-xl bg-zem-gold py-4 font-extrabold text-white">Place order</button>
                </div>
            </div>
        </form>
    </div>
</main>

@push('structured-data')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Restaurant",
    "name": "{{ $restaurant->name }}",
    "description": "Digital QR menu for {{ $restaurant->name }} in {{ $restaurant->location }}. Order directly from your table with ZemTab.",
    "url": "{{ route('menu.show', [$restaurant->slug, $table->table_number]) }}",
    "image": "{{ $restaurant->logo_path ? asset($restaurant->logo_path) : asset('logo/zemtab-full-transparent.png') }}",
    "address": {
        "@type": "PostalAddress",
        "addressLocality": "{{ $restaurant->location }}",
        "addressCountry": "Ethiopia"
    },
    "menu": "{{ route('menu.show', [$restaurant->slug, $table->table_number]) }}",
    "servesCuisine": "Various",
    "currenciesAccepted": "ETB",
    "paymentAccepted": "Cash, Mobile Money, Bank Transfer",
    "priceRange": "$$"
}
</script>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@type": "ListItem",
            "position": 1,
            "name": "ZemTab",
            "item": "{{ url('/') }}"
        },
        {
            "@type": "ListItem",
            "position": 2,
            "name": "{{ $restaurant->name }} Menu",
            "item": "{{ route('menu.show', [$restaurant->slug, $table->table_number]) }}"
        }
    ]
}
</script>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Menu",
    "name": "{{ $restaurant->name }} Digital Menu",
    "hasMenuItem": [
        @foreach($categories as $category)
        @foreach($category->menuItems as $item)
        {
            "@type": "MenuItem",
            "name": "{{ $item->name }}",
            "description": "{{ $item->description }}",
            "offers": {
                "@type": "Offer",
                "price": "{{ $item->price }}",
                "priceCurrency": "ETB",
                "availability": "{{ $item->is_available ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}"
            }
        }@if(!$loop->last || !$loop->parent->last),@endif
        @endforeach
        @endforeach
    ]
}
</script>
@endpush

<script>
function menuCart() {
    return {
        open: false,
        items: [],
        add(item) { const existing = this.items.find(i => i.id === item.id); existing ? existing.quantity++ : this.items.push({...item, quantity: 1, note: ''}); },
        dec(id) { const item = this.items.find(i => i.id === id); if (!item) return; item.quantity--; if (item.quantity <= 0) this.items = this.items.filter(i => i.id !== id); },
        count() { return this.items.reduce((sum, item) => sum + item.quantity, 0); },
        total() { return this.items.reduce((sum, item) => sum + item.quantity * item.price, 0); },
        money(value) { return new Intl.NumberFormat('en-US').format(value) + ' ETB'; },
        syncForm() {
            const holder = document.getElementById('cart-fields');
            holder.innerHTML = '';
            this.items.forEach((item, index) => {
                holder.insertAdjacentHTML('beforeend', `<input type="hidden" name="items[${index}][id]" value="${item.id}"><input type="hidden" name="items[${index}][quantity]" value="${item.quantity}"><input type="hidden" name="items[${index}][note]" value="${(item.note || '').replace(/"/g, '"')}">`);
            });
        }
    }
}
</script>
@endsection
