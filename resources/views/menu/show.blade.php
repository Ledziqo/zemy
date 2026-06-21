@extends('layouts.app', [
    'title' => $restaurant->name.' Menu - '.$restaurant->locationLabelTitle().' '.$table->table_number.' | ZemTab',
    'description' => 'View the digital QR menu for '.$restaurant->name.'. '.$restaurant->locationLabelTitle().' '.$table->table_number.'.',
    'keywords' => $restaurant->name.' menu, QR menu, '.$restaurant->locationLabel().' ordering, ZemTab',
    'canonical' => route('menu.show', [$restaurant->slug, $table->table_number]),
    'ogType' => 'website',
    'ogImage' => $restaurant->cover_image_path ? asset($restaurant->cover_image_path) : asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png'),
    'robots' => 'index, follow',
    'accentColor' => $restaurant->primary_color,
])

@section('content')
@php
    $settings = $restaurant->settings ?? [];
    $enabledPaymentMethods = $settings['payment_methods'] ?? ['cash', 'telebirr', 'cbe'];
    $logoUrl = $restaurant->logo_path ? (\Illuminate\Support\Str::startsWith($restaurant->logo_path, ['http://', 'https://', 'uploads/']) ? (str_starts_with($restaurant->logo_path, 'uploads/') ? asset($restaurant->logo_path) : $restaurant->logo_path) : asset('storage/'.$restaurant->logo_path)) : null;
    $placeTitle = $restaurant->locationLabelTitle();
    $visitOrders = $visit?->orders?->sortByDesc('created_at') ?? collect();
    $visitRequests = $visit?->serviceRequests ?? collect();
    
    $visitTotal = $visitOrders->whereNotIn('status', ['cancelled'])->sum(fn ($order) => (float) $order->total);

    $allPaymentMethods = [
        'cash' => ['label' => 'Cash', 'logo' => null, 'account_field' => null, 'qr_field' => null],
        'telebirr' => ['label' => 'Telebirr', 'logo' => asset('bank-logos/telebirr.png'), 'account_field' => 'telebirr_number', 'qr_field' => 'telebirr_qr_path'],
        'cbe' => ['label' => 'CBE', 'logo' => asset('bank-logos/cbe.png'), 'account_field' => 'cbe_account_number', 'qr_field' => 'cbe_qr_path'],
        'awash' => ['label' => 'Awash Bank', 'logo' => asset('bank-logos/awash.jpg'), 'account_field' => 'awash_account_number', 'qr_field' => 'awash_qr_path'],
        'abyssinia' => ['label' => 'Bank of Abyssinia', 'logo' => asset('bank-logos/abyssinia.jpg'), 'account_field' => 'abyssinia_account_number', 'qr_field' => 'abyssinia_qr_path'],
    ];
    $activePaymentMethods = collect($allPaymentMethods)->filter(fn ($m, $key) => in_array($key, $enabledPaymentMethods, true));
@endphp
<main x-data="{ ...menuCart({ paymentDetails: {} }), paymentOpen: false, selectedPayment: null }" class="min-h-screen bg-neutral-100 pb-28 text-zem-ink">
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
                <a href="#all" @click="activeCategory = 'all'" :class="activeCategory === 'all' ? 'bg-zem-gold text-white' : 'border border-black/10 bg-white text-neutral-700'" class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-extrabold transition">All</a>
                @foreach($categories as $category)
                    <a href="#cat-{{ $category->id }}" @click="activeCategory = 'cat-{{ $category->id }}'" :class="activeCategory === 'cat-{{ $category->id }}' ? 'bg-zem-gold text-white' : 'border border-black/10 bg-white text-neutral-700'" class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-extrabold transition">{{ $category->name }}</a>
                @endforeach
            </nav>
        </div>
    </header>

    <section class="mx-auto max-w-5xl px-4 py-4">
        @if(session('success'))<div class="mb-4 rounded-xl bg-zem-green px-4 py-3 text-sm font-bold text-white">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="mb-4 rounded-xl bg-red-600 px-4 py-3 text-sm font-bold text-white">{{ $errors->first() }}</div>@endif
        <div class="grid grid-cols-3 gap-3">
            <form method="post" action="{{ route('service-requests.store', [$restaurant->slug, $table->table_number]) }}">@csrf<input type="hidden" name="type" value="call_waiter"><button class="w-full rounded-xl bg-black px-4 py-3 font-extrabold text-white">{{ $restaurant->staffRequestLabel() }}</button></form>
            <form method="post" action="{{ route('service-requests.store', [$restaurant->slug, $table->table_number]) }}">@csrf<input type="hidden" name="type" value="request_bill"><button class="w-full rounded-xl bg-zem-gold px-4 py-3 font-extrabold text-white">Request Bill</button></form>
            @if($activePaymentMethods->isNotEmpty())
                <button type="button" @click="paymentOpen = true; selectedPayment = null" class="w-full rounded-xl border-2 border-black/10 bg-white px-4 py-3 font-extrabold text-black">
                    Payment
                </button>
            @endif
        </div>
    </section>

    @if($visitOrders->isNotEmpty())
        <section class="mx-auto max-w-5xl px-4 pb-4">
            <div class="rounded-2xl border border-black/10 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-widest text-zem-gold">Your visit</p>
                        <h2 class="font-display text-2xl font-extrabold">{{ number_format($visitTotal) }} ETB</h2>
                    </div>
                    <p class="rounded-full bg-neutral-100 px-3 py-2 text-xs font-bold text-neutral-600">Active until {{ $visit->expires_at->format('H:i') }}</p>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <div class="rounded-xl bg-neutral-50 p-3">
                        <h3 class="font-extrabold">Orders</h3>
                        <div class="mt-2 space-y-2">
                            @foreach($visitOrders->take(4) as $order)
                                @php($cancelDeadline = $order->created_at->copy()->addMinutes(2))
                                <div class="rounded-lg border border-black/10 bg-white p-3 text-sm" @if($order->status === 'new' && $cancelDeadline->isFuture()) x-data="cancelTimer(@js($cancelDeadline->toIso8601String()))" x-init="start()" @endif>
                                    <div class="flex items-center justify-between gap-3"><strong>#{{ $order->id }} - {{ ucfirst($order->status) }}</strong><strong>{{ number_format($order->total) }} ETB</strong></div>
                                    <p class="mt-1 text-neutral-500">{{ $order->created_at->format('H:i') }}</p>
                                    <ul class="mt-2 space-y-1 text-neutral-700">
                                        @foreach($order->items as $item)
                                            <li>{{ $item->quantity }} &times; {{ $item->item_name }}</li>
                                        @endforeach
                                    </ul>
                                    @if($order->status === 'new' && $cancelDeadline->isFuture())
                                        <form method="post" action="{{ route('orders.cancel', [$restaurant->slug, $table->table_number, $order]) }}" class="mt-3" x-show="remaining > 0" onsubmit="return confirm('Cancel this order?')">
                                            @csrf @method('PATCH')
                                            <button class="w-full rounded-lg border border-red-300 bg-red-50 px-3 py-2 font-bold text-red-700">Cancel order &middot; <span x-text="clock"></span></button>
                                        </form>
                                        <p x-show="remaining <= 0" x-cloak class="mt-3 text-xs font-bold text-neutral-500">Cancellation window ended</p>
                                    @elseif($order->status === 'new')
                                        <p class="mt-3 text-xs font-bold text-neutral-500">Cancellation window ended</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="rounded-xl bg-neutral-50 p-3">
                        <h3 class="font-extrabold">Requests & payment</h3>
                        <div class="mt-2 space-y-2 text-sm">
                            @forelse($visitRequests->take(3) as $requestRow)
                                <p class="rounded-lg border border-black/10 bg-white p-3">{{ $restaurant->requestTypeLabel($requestRow->type) }} - {{ ucfirst($requestRow->status) }}</p>
                            @empty
                                <p class="rounded-lg border border-black/10 bg-white p-3 text-neutral-500">No service requests yet.</p>
                            @endforelse
                            
                        </div>
                    </div>
                </div>

            </div>
        </section>
    @endif

    {{-- Payment Methods Modal --}}
    @if($activePaymentMethods->isNotEmpty())
    <div x-show="paymentOpen" x-cloak class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm" @click.self="paymentOpen=false">
        <div class="absolute bottom-0 max-h-[92vh] w-full overflow-y-auto rounded-t-3xl bg-white p-4 text-zem-ink shadow-2xl">
            <div class="mx-auto max-w-3xl">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-2xl font-extrabold">Payment</h2>
                    <button type="button" @click="paymentOpen=false" class="rounded-lg border border-black/10 px-3 py-2 font-bold">Close</button>
                </div>

                {{-- Step 1: Select payment method --}}
                <div x-show="selectedPayment === null" class="mt-5">
                    <p class="text-sm text-neutral-500">Choose a payment method to see account details and QR code.</p>
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach($activePaymentMethods as $method => $meta)
                            <button type="button" @click="selectedPayment = '{{ $method }}'" class="flex items-center gap-3 rounded-2xl border border-black/10 bg-neutral-50 p-4 text-left transition hover:border-zem-gold hover:bg-zem-gold/10">
                                @if($meta['logo'])
                                    <img src="{{ $meta['logo'] }}" alt="{{ $meta['label'] }} logo" class="h-12 w-12 shrink-0 rounded-lg border border-black/10 bg-white object-contain p-1">
                                @else
                                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-lg border border-black/10 bg-white text-xl font-extrabold">$</div>
                                @endif
                                <div class="min-w-0">
                                    <p class="font-extrabold">{{ $meta['label'] }}</p>
                                    @if($method === 'cash')
                                        <p class="text-sm text-neutral-500">Pay with cash when staff brings your order or bill.</p>
                                    @else
                                        <p class="text-sm text-neutral-500">Transfer to {{ $meta['label'] }} and show proof to staff.</p>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                    @if($visitTotal > 0)
                        <p class="mt-4 rounded-xl bg-zem-gold/10 px-4 py-3 text-sm font-bold text-zem-gold">Current total: {{ number_format($visitTotal) }} ETB</p>
                    @endif
                </div>

                {{-- Step 2: Show account details + QR for selected method --}}
                <div x-show="selectedPayment !== null" class="mt-5">
                    <button type="button" @click="selectedPayment = null" class="mb-4 inline-flex items-center gap-1 text-sm font-bold text-zem-gold">&larr; Back to methods</button>
                    @foreach($activePaymentMethods as $method => $meta)
                        <div x-show="selectedPayment === '{{ $method }}'">
                            <div class="flex items-center gap-3 rounded-2xl border border-black/10 bg-neutral-50 p-4">
                                @if($meta['logo'])
                                    <img src="{{ $meta['logo'] }}" alt="{{ $meta['label'] }} logo" class="h-16 w-16 shrink-0 rounded-lg border border-black/10 bg-white object-contain p-2">
                                @else
                                    <div class="grid h-16 w-16 shrink-0 place-items-center rounded-lg border border-black/10 bg-white text-2xl font-extrabold">$</div>
                                @endif
                                <div>
                                    <p class="text-xs font-extrabold uppercase tracking-widest text-zem-gold">Selected method</p>
                                    <p class="font-display text-xl font-extrabold">{{ $meta['label'] }}</p>
                                </div>
                            </div>

                            @if($method === 'cash')
                                <div class="mt-4 rounded-xl border border-black/10 bg-neutral-50 p-4">
                                    <p class="font-extrabold">Cash payment</p>
                                    <p class="mt-2 text-sm text-neutral-600">Pay with cash when staff brings your order or bill. Request bill to call a staff member to confirm your final amount.</p>
                                </div>
                            @else
                                @php($accountNumber = $settings[$meta['account_field']] ?? null)
                                @php($qrPath = $settings[$meta['qr_field']] ?? null)
                                @php($qrUrl = ! empty($qrPath) ? asset($qrPath) : null)
                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-xl border border-black/10 bg-neutral-50 p-4">
                                        <p class="text-xs font-extrabold uppercase tracking-widest text-zem-gold">Account number</p>
                                        <p class="mt-2 break-words text-lg font-extrabold">{{ $accountNumber ?? 'Ask staff for the account number.' }}</p>
                                        <p class="mt-3 text-sm text-neutral-600">Transfer to this {{ $meta['label'] }} account, then show your payment screenshot to staff for confirmation.</p>
                                    </div>
                                    <div class="rounded-xl border border-black/10 bg-neutral-50 p-4">
                                        <p class="text-xs font-extrabold uppercase tracking-widest text-zem-gold">Payment QR</p>
                                        @if($qrUrl)
                                            <img src="{{ $qrUrl }}" alt="{{ $meta['label'] }} payment QR" class="mt-2 h-40 w-40 rounded-lg border border-black/10 bg-white object-contain p-2">
                                            <p class="mt-2 text-xs text-neutral-500">Scan this QR code with your banking app to pay.</p>
                                        @else
                                            <div class="mt-2 grid h-40 w-40 place-items-center rounded-lg border border-black/10 bg-white p-2 text-center text-xs font-bold text-neutral-500">QR not added</div>
                                        @endif
                                    </div>
                                </div>

                                {@endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

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
                                    <img src="{{ $imageUrl }}" alt="{{ $item->name }}" class="h-full w-full object-cover" loading="lazy">
                                @else
                                    <div class="grid h-full place-items-center text-5xl font-extrabold text-white" style="background: linear-gradient(135deg, #111, var(--zem-accent));">{{ strtoupper(substr($item->name, 0, 1)) }}</div>
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
        activeCategory: 'all',
        items: [],
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

function cancelTimer(deadline) {
    return {
        remaining: Math.max(0, Math.ceil((new Date(deadline).getTime() - Date.now()) / 1000)),
        timer: null,
        get clock() {
            const minutes = Math.floor(this.remaining / 60);
            return minutes + ':' + String(this.remaining % 60).padStart(2, '0');
        },
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
