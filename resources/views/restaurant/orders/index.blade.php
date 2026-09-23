@extends('layouts.dashboard', ['heading' => __('Work Board'), 'eyebrow' => __('Live orders')])

@section('content')
@include('restaurant.partials.order_sound_alerts', ['latestOrderId' => $latestOrderId])
@php($placeTitle = __($restaurant->locationLabelTitle()))
@php($isHotel = $restaurant->isHotel())
@php($manualOrderTitle = $isHotel ? __('Manual Room Service Order') : __('Manual Order'))
@php($placeOptionLabel = $isHotel ? __('Room') : __('Table'))
@php($placeSelectLabel = $isHotel ? __('Select room') : __('Select table'))
@php($customerLabel = $isHotel ? __('Guest') : __('Customer'))
@php($createManualOrderLabel = $isHotel ? __('Create Room Service Order') : __('Create Manual Order'))
@php($requestTypeLabels = ['call_waiter' => __($restaurant->staffRequestLabel()), 'request_bill' => __('Request Bill'), 'request_water' => __('Request Water'), 'other' => __('Other')])
@php($staffRole = session('staff_profile_role', 'owner_manager'))
@php($kitchenScreenEnabled = $restaurant->kitchenScreenEnabled())
@php($paymentMethods = $paymentMethods ?? ['cash', 'telebirr', 'cbe', 'awash', 'abyssinia'])
<div x-data="workBoard()" x-cloak>
<div class="mb-4 flex flex-wrap items-center gap-4 rounded-md border border-zem-border bg-zem-card px-4 py-3 text-sm" role="status">
    <span>{{ __('Active orders') }}: <strong x-text="activeCount">{{ $activeCount }}</strong></span>
    @if($staffRole !== 'kitchen')<span>{{ __('Completed today') }}: <strong x-text="completedCount">{{ $completedCount }}</strong></span>@endif
    @if($staffRole !== 'kitchen')
        <span>{{ __('Active requests') }}: <strong x-text="activeRequests">{{ $activeRequests }}</strong></span>
    @endif
    <span class="text-zem-muted" x-text="pollError || nextPollLabel()"></span>
    <button type="button" @click="poll()" :disabled="polling" class="rounded-md border border-zem-border px-3 py-2 font-bold disabled:opacity-50">{{ __('Refresh') }}</button>
</div>

<div class="grid gap-5 {{ $staffRole === 'kitchen' ? '' : 'xl:grid-cols-[minmax(0,1.5fr)_minmax(360px,.9fr)]' }}">
    <section>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-display text-xl font-bold">{{ __('Orders') }}</h2>
            <div class="flex gap-2">
                <button @click="changeFilter('active')" :class="filter==='active'?'bg-zem-gold text-white':'border border-zem-border'" class="rounded-full px-4 py-2 text-sm font-bold">{{ __('Active') }}</button>
                <button @click="changeFilter('completed')" :class="filter==='completed'?'bg-zem-gold text-white':'border border-zem-border'" class="rounded-full px-4 py-2 text-sm font-bold">{{ $staffRole === 'kitchen' ? __('Ready / done') : __('Completed') }}</button>
                <button @click="changeFilter('all')" :class="filter==='all'?'bg-zem-gold text-white':'border border-zem-border'" class="rounded-full px-4 py-2 text-sm font-bold">{{ __('All') }}</button>
            </div>
        </div>
        <div class="grid gap-4" x-ref="ordersList">
            @forelse($orders as $order)
                @php($needsConfirmation = $order->guest_session_id && ! $order->confirmed_at && ! in_array($order->status, ['completed', 'cancelled'], true))
                <article class="rounded-md border-l-4 border border-zem-border bg-zem-card p-4 {{ in_array($order->status, ['completed', 'cancelled'], true) ? 'border-l-gray-400 opacity-60' : ($needsConfirmation ? 'border-l-yellow-400' : 'border-l-zem-gold') }}" data-order-id="{{ $order->id }}" data-status="{{ $order->status }}" data-payment-status="{{ $order->payment_status }}" data-payment-method="{{ $order->payment_method }}" data-confirmed="{{ $order->confirmed_at ? '1' : '0' }}" data-needs-confirmation="{{ $needsConfirmation ? '1' : '0' }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><h2 class="font-display text-xl font-bold">{{ __('Order') }} #{{ $order->id }}</h2><p class="text-sm text-zem-muted">{{ $order->table?->displayLabel() ?: $order->table_number }}@if(($order->order_type ?? 'dine_in') === 'delivery') <span class="ml-1 rounded-full bg-purple-100 px-2 py-0.5 text-xs font-bold text-purple-700">Driver pickup</span>@endif @if($needsConfirmation)<span class="ml-1 rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-bold text-yellow-700">{{ __('Needs cashier confirm') }}</span>@endif - <span data-created-at="{{ $order->created_at->toIso8601String() }}">{{ $order->created_at->diffForHumans() }}</span></p></div>
                        <span data-status-badge><x-status :status="$order->status" /></span>
                    </div>
                    <div class="mt-4 space-y-2">
                        @foreach($order->items as $item)<p class="flex justify-between gap-3 rounded-md border border-zem-border bg-zem-bg px-3 py-2 text-sm text-zem-cream"><span>{{ $item->quantity }} x {{ $item->item_name }} @if($item->note)<em class="text-zem-muted">({{ $item->note }})</em>@endif</span><strong class="shrink-0 text-zem-cream">{{ number_format($item->total_price) }} ETB</strong></p>@endforeach
                    </div>
                    @if($order->note)<p class="mt-3 text-sm text-zem-muted">{{ __('Note') }}: {{ $order->note }}</p>@endif
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3" data-order-actions>
                        <strong>{{ number_format($order->total, 2) }} ETB</strong>
                        @if($order->status === 'completed' && $order->payment_method === 'room_credit' && $order->payment_status !== 'paid' && in_array($staffRole, ['owner_manager', 'cashier'], true))
                            <span class="rounded-md border border-zem-gold/40 bg-zem-gold/10 px-4 py-3 text-sm font-bold text-zem-gold">Room credit · unpaid</span>
                            <button type="button" @click="markCreditPaid({{ $order->id }})" class="rounded-md bg-emerald-600 px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]">Mark credit paid</button>
                        @elseif(!in_array($order->status, ['completed', 'cancelled'], true))
                            @if(in_array($staffRole, ['owner_manager', 'cashier'], true) && $needsConfirmation)
                                <button type="button" @click="confirmOrder({{ $order->id }})" class="rounded-md bg-yellow-500 px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]">{{ __('Confirm order') }}</button>
                            @elseif(! $kitchenScreenEnabled && in_array($staffRole, ['owner_manager', 'cashier'], true))
                                <button type="button" @click="markCompleted({{ $order->id }})" class="rounded-md bg-zem-green px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]">{{ __('Mark as completed') }}</button>
                            @elseif(in_array($staffRole, ['owner_manager', 'cashier'], true) && $order->status === 'served')
                                <div class="flex gap-2">
                                    <select id="payment-method-{{ $order->id }}" class="rounded-md border border-zem-border bg-white px-3 py-3 text-sm">
                                        <option value="">Payment method...</option>
                                        @foreach($paymentMethods as $method)
                                            <option value="{{ $method }}">{{ $method === 'room_credit' ? 'Room credit (pay later)' : ucfirst($method) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" @click="markPayment({{ $order->id }})" class="rounded-md bg-emerald-600 px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]">Apply payment</button>
                                </div>
                            @elseif($staffRole === 'cashier' && !in_array($order->status, ['paid']))
                                <span class="rounded-md border border-zem-border bg-zem-soft px-4 py-3 text-sm font-bold text-zem-muted">{{ __('Waiting for kitchen') }}</span>
                            @elseif($staffRole === 'cashier' && $order->status === 'paid')
                                <button type="button" @click="markCompleted({{ $order->id }})" class="rounded-md bg-zem-green px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]">{{ __('Mark as completed') }}</button>
                            @elseif($staffRole === 'kitchen')
                                @if($order->status === 'new')
                                    <button type="button" @click="updateStatus({{ $order->id }}, 'preparing')" class="rounded-md bg-blue-600 px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]">Start Preparing</button>
                                @elseif($order->status === 'preparing')
                                    <button type="button" @click="updateStatus({{ $order->id }}, 'served')" class="rounded-md bg-green-600 px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]">Mark Served</button>
                                @endif
                            @else
                                <button type="button" @click="markCompleted({{ $order->id }})" class="rounded-md bg-zem-green px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]">{{ __('Mark as completed') }}</button>
                            @endif
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-md border border-zem-border bg-zem-card p-8 text-center">
                    <div class="mb-2 text-sm font-bold uppercase tracking-widest text-zem-gold">Orders</div>
                    <p class="text-zem-muted">{{ __('No orders in this view.') }}</p>
                    @if($staffRole !== 'kitchen')<a href="{{ route('restaurant.tables.index') }}" class="mt-3 inline-block rounded-md border border-zem-gold px-4 py-2 text-sm font-bold text-zem-gold">Go to QR codes</a>@endif
                </div>
            @endforelse
        </div>
        <div class="mt-5" x-ref="pagination">@if($filter !== 'active'){{ $orders->links() }}@endif</div>
    </section>

    <aside @if($staffRole === 'kitchen') hidden @endif>
        <div class="sticky top-4">
            @if(in_array($staffRole, ['owner_manager', 'cashier'], true))
                <div class="mb-5 rounded-md border border-zem-border bg-zem-card p-4" x-data="manualOrder()">
                    <div class="flex items-center justify-between gap-3">
                        <button type="button" @click="formOpen = !formOpen" :aria-expanded="formOpen" class="font-display text-xl font-bold">{{ $manualOrderTitle }} <span x-text="formOpen ? '−' : '+'"></span></button>
                        <span class="rounded-full border border-zem-border px-3 py-1 text-xs font-bold text-zem-muted" x-text="count() + ' item(s)'"></span>
                    </div>
                    <form method="post" action="{{ route('restaurant.orders.manual.store') }}" x-show="formOpen" x-cloak class="mt-4 space-y-3" @submit="syncForm($event)">
                        @csrf
                        <label class="grid gap-1 text-sm">
                            <span class="font-bold">{{ __('Order type') }}</span>
                            <select name="order_mode" x-model="mode" class="rounded-md border border-zem-border bg-white px-3 py-2">
                                <option value="table">{{ $placeOptionLabel }}</option>
                                <option value="takeaway">{{ $isHotel ? __('Walk-in / lobby') : __('Takeaway') }}</option>
                                <option value="delivery">{{ $isHotel ? __('External driver pickup') : __('Driver pickup order') }}</option>
                            </select>
                        </label>

                        <div class="grid gap-3 sm:grid-cols-2" x-show="mode === 'table'" x-cloak>
                            <label class="grid gap-1 text-sm">
                                <span class="font-bold">{{ $placeTitle }}</span>
                                <select name="table_number" :disabled="mode !== 'table'" class="rounded-md border border-zem-border bg-white px-3 py-2">
                                    <option value="">{{ $placeSelectLabel }}</option>
                                    @foreach($tables as $table)
                                        <option value="{{ $table->table_number }}">{{ $table->table_name ?: $table->table_number }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-1 text-sm">
                                <span class="font-bold">{{ $customerLabel }}</span>
                                <input name="customer_name" placeholder="{{ __('Optional') }}" class="rounded-md border border-zem-border bg-white px-3 py-2">
                            </label>
                        </div>

                        <label class="grid gap-1 text-sm" x-show="mode === 'delivery'" x-cloak>
                            <span class="font-bold">{{ $isHotel ? __('Pickup source / driver') : __('Pickup source / driver') }}</span>
                            <input name="delivery_app" :disabled="mode !== 'delivery'" placeholder="{{ $isHotel ? __('Front desk, guest call, app...') : __('Bolt, Glovo, phone call...') }}" class="rounded-md border border-zem-border bg-white px-3 py-2">
                        </label>

                        <div class="rounded-md border border-zem-border bg-zem-bg p-3">
                            <button type="button" @click="menuOpen = true" class="w-full rounded-md bg-zem-gold px-4 py-3 text-base font-bold text-white">{{ __('Open menu') }}</button>
                            <div class="mt-3 space-y-2" x-show="items.length > 0" x-cloak>
                                <template x-for="item in items" :key="item.id">
                                    <div class="rounded-md border border-zem-border bg-zem-card p-2 text-sm">
                                        <div class="flex items-center justify-between gap-2">
                                            <div>
                                                <p class="font-bold" x-text="item.name"></p>
                                                <p class="text-xs text-zem-muted" x-text="money(item.price)"></p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button type="button" @click="dec(item.id)" class="h-9 w-9 rounded-md border border-zem-border font-bold">-</button>
                                                <span class="w-7 text-center font-bold" x-text="item.qty"></span>
                                                <button type="button" @click="add(item)" class="h-9 w-9 rounded-md border border-zem-border font-bold">+</button>
                                            </div>
                                        </div>
                                        <input type="text" x-model="item.note" placeholder="{{ __('Item note') }}" class="mt-2 w-full rounded-md border border-zem-border bg-white px-3 py-2 text-sm">
                                    </div>
                                </template>
                                <div class="flex items-center justify-between border-t border-zem-border pt-3 text-sm font-bold">
                                    <span>{{ __('Total') }}</span>
                                    <span x-text="money(total())"></span>
                                </div>
                            </div>
                        </div>

                        <textarea name="note" rows="2" placeholder="{{ $isHotel ? __('Room service note') : __('Order note') }}" class="w-full rounded-md border border-zem-border bg-white px-3 py-2 text-sm"></textarea>
                        <div x-ref="fields"></div>
                        <button class="w-full rounded-md bg-zem-gold px-4 py-3 text-base font-bold text-white">{{ $createManualOrderLabel }}</button>
                    </form>

                    <div x-show="menuOpen" x-cloak class="fixed inset-0 z-50 bg-black/60 p-3 backdrop-blur-sm" @click.self="menuOpen = false">
                        <div class="mx-auto flex max-h-[92vh] max-w-md flex-col overflow-hidden rounded-2xl border border-zem-border bg-zem-card shadow-2xl">
                            <div class="flex items-center justify-between border-b border-zem-border px-4 py-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-zem-gold">{{ $manualOrderTitle }}</p>
                                    <h3 class="font-display text-xl font-bold">{{ __('Menu') }}</h3>
                                </div>
                                <button type="button" @click="menuOpen = false" class="rounded-md border border-zem-border px-3 py-2 text-sm font-bold">{{ __('Close') }}</button>
                            </div>
                            <div class="flex gap-2 overflow-x-auto border-b border-zem-border px-4 py-3">
                                @foreach($categories as $category)
                                    <a href="#manual-cat-{{ $category->id }}" class="whitespace-nowrap rounded-full border border-zem-border px-3 py-1 text-xs font-bold text-zem-muted">{{ $category->name }}</a>
                                @endforeach
                            </div>
                            <div class="flex-1 space-y-5 overflow-y-auto p-4">
                                @foreach($categories as $category)
                                    <section id="manual-cat-{{ $category->id }}" class="scroll-mt-4">
                                        <h4 class="mb-2 font-display text-lg font-bold">{{ $category->name }}</h4>
                                        <div class="grid gap-2">
                                            @foreach($category->menuItems as $item)
                                                @php($imageUrl = \App\Support\MenuImage::url($item->image_path))
                                                <article class="flex gap-3 rounded-md border border-zem-border bg-zem-bg p-2">
                                                    <div class="h-16 w-16 shrink-0 overflow-hidden rounded-md bg-zem-soft">
                                                        @if($imageUrl)
                                                            <img src="{{ $imageUrl }}" alt="{{ $item->name }}" width="64" height="64" loading="lazy" decoding="async" class="h-full w-full object-cover">
                                                        @else
                                                            <div class="grid h-full place-items-center text-lg font-bold">{{ strtoupper(substr($item->name, 0, 1)) }}</div>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <p class="line-clamp-1 font-bold">{{ $item->name }}</p>
                                                        <p class="line-clamp-2 text-xs text-zem-muted">{{ $item->description }}</p>
                                                        <p class="mt-1 text-sm font-bold text-zem-gold">{{ number_format($item->price) }} ETB</p>
                                                    </div>
                                                    <button type="button" @click="add({ id: {{ $item->id }}, name: @js($item->name), price: {{ (float) $item->price }} })" class="self-center rounded-md bg-zem-gold px-3 py-2 text-sm font-bold text-white">{{ __('Add') }}</button>
                                                </article>
                                            @endforeach
                                        </div>
                                    </section>
                                @endforeach
                            </div>
                            <div class="border-t border-zem-border px-4 py-3">
                                <button type="button" @click="menuOpen = false" class="w-full rounded-md bg-zem-gold px-4 py-3 text-sm font-bold text-white"><span x-text="count()"></span> {{ __('item(s)') }} - <span x-text="money(total())"></span></button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if($staffRole !== 'kitchen')
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-display text-xl font-bold">{{ __('Service Requests') }}</h2>
            </div>
            @endif
            <div class="grid gap-3" x-ref="requestsList">
                @forelse($staffRole === 'kitchen' ? collect() : $requests as $requestRow)
                    <div class="rounded-md border {{ in_array($requestRow->status, ['pending', 'acknowledged'], true) ? 'border-zem-gold/40 bg-zem-gold/10' : 'border-zem-border bg-zem-card opacity-60' }} p-4" data-request-id="{{ $requestRow->id }}" data-status="{{ $requestRow->status }}" x-show="filter === 'all' || (filter === 'active' && '{{ $requestRow->status }}' !== 'completed') || (filter === 'completed' && '{{ $requestRow->status }}' === 'completed')">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <strong>{{ $placeTitle }} {{ $requestRow->table_number }}</strong>
                                <p class="mt-1 text-sm text-zem-muted">{{ $requestTypeLabels[$requestRow->type] ?? $restaurant->requestTypeLabel($requestRow->type) }} - <span data-created-at="{{ $requestRow->created_at->toIso8601String() }}">{{ $requestRow->created_at->diffForHumans() }}</span></p>
                                @if($requestRow->note)<p class="mt-2 text-sm">{{ $requestRow->note }}</p>@endif
                            </div>
                            <span data-status-badge><x-status :status="$requestRow->status" /></span>
                        </div>
                        @if($requestRow->status !== 'completed')
                            <button type="button" @click="markRequestCompleted({{ $requestRow->id }})" class="mt-3 w-full rounded-md bg-zem-green px-4 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]">{{ __('Mark as completed') }}</button>
                        @endif
                    </div>
                @empty
                    @if($staffRole !== 'kitchen')
                    <div class="rounded-md border border-zem-border bg-zem-card p-6 text-center">
                        <div class="mb-2 text-sm font-bold uppercase tracking-widest text-zem-gold">Requests</div>
                        <p class="text-zem-muted text-sm">{{ __('No service requests yet.') }}</p>
                    </div>
                    @endif
                @endforelse
            </div>
        </div>
    </aside>
</div>

<div x-show="toast" x-cloak x-transition class="fixed bottom-4 right-4 z-50 rounded-lg border border-zem-border bg-zem-card px-4 py-3 text-sm font-bold shadow-2xl" :class="toastType === 'error' ? 'border-red-400 text-red-700' : 'border-zem-green/40 text-zem-cream'">
    <span x-text="toastMessage"></span>
</div>
</div>

<script>
function manualOrder() {
    return {
        formOpen: false,
        mode: 'table',
        menuOpen: false,
        items: [],
        add(item) {
            const existing = this.items.find(row => row.id === item.id);
            if (existing) {
                existing.qty++;
                return;
            }
            this.items.push({ id: item.id, name: item.name, price: Number(item.price || 0), qty: 1, note: '' });
        },
        dec(id) {
            const item = this.items.find(row => row.id === id);
            if (! item) return;
            item.qty--;
            if (item.qty <= 0) this.items = this.items.filter(row => row.id !== id);
        },
        count() {
            return this.items.reduce((sum, item) => sum + Number(item.qty || 0), 0);
        },
        total() {
            const subtotal = this.items.reduce((sum, item) => sum + Number(item.qty || 0) * Number(item.price || 0), 0);
            return subtotal * (1 + {{ (float) ($restaurant->settings['service_charge_percentage'] ?? 0) }} / 100 + {{ (float) ($restaurant->settings['vat_percentage'] ?? 0) }} / 100);
        },
        money(value) {
            return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(value) + ' ETB';
        },
        syncForm(event) {
            if (this.items.length === 0) {
                event.preventDefault();
                alert('Add at least one item');
                return;
            }

            this.$refs.fields.innerHTML = '';
            this.items.forEach((item, index) => {
                [['id', item.id], ['quantity', item.qty], ['note', item.note || '']].forEach(([field, value]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'items[' + index + '][' + field + ']';
                    input.value = value;
                    this.$refs.fields.appendChild(input);
                });
            });
        },
    };
}

function workBoard() {
    return {
        filter: @js($filter),
        page: {{ max(1, (int) request('page', 1)) }},
        pollError: '',
        initialized: false,
        pendingOrders: new Set(),
        activeCount: 0,
        completedCount: 0,
        activeRequests: 0,
        nextPollAt: null,
        nextPollSeconds: 30,
        countdownTimer: null,
        toast: false,
        toastMessage: '',
        toastType: 'success',
        latestOrderId: 0,
        latestConfirmedAt: null,
        latestRequestId: 0,
        pollTimer: null,
        pollDelay: 30000,
        polling: false,
        pollUrl: @js($pollUrl),
        pollRevision: null,
        orderUpdateUrl: '{{ route("restaurant.orders.update", ["__ID__"]) }}',
        orderConfirmUrl: '{{ route("restaurant.orders.confirm", ["__ID__"]) }}',
        creditPaidUrl: '{{ route("restaurant.orders.credit-paid", ["__ID__"]) }}',
        requestUpdateUrl: '{{ route("restaurant.service-requests.update", ["__ID__"]) }}',
        staffRole: @js($staffRole),
        kitchenScreenEnabled: @js($kitchenScreenEnabled),
        paymentMethods: @js($paymentMethods),
        requestTypeLabels: @js($requestTypeLabels),
        placeTitle: @js($placeTitle),
        labels: @js([
            'order' => __('Order'), 'note' => __('Note'), 'none' => __('None'),
            'markCompleted' => __('Mark as completed'), 'requestCompleted' => __('Request completed'),
            'confirmOrder' => __('Confirm order'), 'needsConfirm' => __('Needs cashier confirm'),
            'failedRequest' => __('Failed to update request'), 'failedOrder' => __('Failed to update order'),
        ]),
        statusLabels: @js(collect(['new','preparing','served','paid','completed','cancelled','pending','acknowledged'])->mapWithKeys(fn ($status) => [$status => __(ucfirst($status))])),

        init() {
            if (this.initialized) return;
            this.initialized = true;
            this.latestOrderId = {{ $latestOrderId }};
            this.latestConfirmedAt = @js($latestConfirmedAt);
            this.latestRequestId = {{ $latestRequestId }};
            this.activeRequests = {{ $activeRequests }};
            this.activeCount = {{ $activeCount }};
            this.completedCount = {{ $completedCount }};
            this.schedulePoll(30000);
            this.visibilityHandler = () => {
                if (document.hidden) {
                    this.schedulePoll(60000);
                    return;
                }
                this.pollDelay = 30000;
                this.poll();
            };
            document.addEventListener('visibilitychange', this.visibilityHandler);
            this.updateRelativeTimes();
            this.relativeTimer = setInterval(() => this.updateRelativeTimes(), 1000);
            this.applyFilter();
            this.countdownTimer = setInterval(() => this.updatePollCountdown(), 1000);
        },

        schedulePoll(delay = this.pollDelay) {
            clearTimeout(this.pollTimer);
            this.nextPollAt = Date.now() + delay;
            this.updatePollCountdown();
            this.pollTimer = setTimeout(() => this.poll(), delay);
        },

        updatePollCountdown() {
            if (this.polling) {
                this.nextPollSeconds = 0;
                return;
            }
            if (!this.nextPollAt) {
                this.nextPollSeconds = Math.ceil(this.pollDelay / 1000);
                return;
            }
            this.nextPollSeconds = Math.max(0, Math.ceil((this.nextPollAt - Date.now()) / 1000));
        },

        nextPollLabel() {
            if (this.polling) return @js(__('Checking now...'));
            return @js(__('Next update in')) + ' ' + this.nextPollSeconds + 's';
        },

        showToast(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;
            this.toast = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => this.toast = false, 3000);
        },

        destroy() {
            clearTimeout(this.pollTimer);
            clearTimeout(this._toastTimer);
            clearInterval(this.countdownTimer);
            clearInterval(this.relativeTimer);
            document.removeEventListener('visibilitychange', this.visibilityHandler);
        },

        changeFilter(filter) {
            const url = new URL(window.location.href);
            url.searchParams.set('filter', filter);
            url.searchParams.delete('page');
            window.location.assign(url);
        },

        async mutateOrder(orderId, status = null, paymentMethod = '') {
            if (this.pendingOrders.has(orderId)) return;
            this.pendingOrders.add(orderId);
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('_method', 'PATCH');
            if (status) formData.append('status', status);
            if (paymentMethod) formData.append('payment_method', paymentMethod);
            try {
                const response = await fetch((status ? this.orderUpdateUrl : this.orderConfirmUrl).replace('__ID__', orderId), {
                    method: 'POST', body: formData, signal: AbortSignal.timeout(15000),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.message || this.labels.failedOrder);
                this.showToast('Order #' + orderId + ' updated');
            } catch (error) {
                this.showToast(error.message || this.labels.failedOrder, 'error');
            } finally {
                this.pendingOrders.delete(orderId);
                // Finish any older snapshot before fetching the result of this action.
                if (this.pollPromise) await this.pollPromise;
                await this.poll();
            }
        },

        confirmOrder(orderId) { return this.mutateOrder(orderId); },
        updateStatus(orderId, status) { return this.mutateOrder(orderId, status); },
        markCompleted(orderId) { return this.mutateOrder(orderId, 'completed'); },
        markPaid(orderId) {
            const method = document.getElementById('payment-method-' + orderId)?.value;
            if (!method) { this.showToast('Select a payment method first', 'error'); return; }
            return this.mutateOrder(orderId, 'paid', method);
        },

        slowConnection() {
            const connection = navigator.connection;
            return Boolean(connection?.saveData || ['slow-2g', '2g'].includes(connection?.effectiveType));
        },
        markPayment(orderId) {
            const method = document.getElementById('payment-method-' + orderId)?.value;
            if (!method) { this.showToast('Select a payment method first', 'error'); return; }
            return this.mutateOrder(orderId, method === 'room_credit' ? 'completed' : 'paid', method);
        },
        markCreditPaid(orderId) {
            if (this.pendingOrders.has(orderId)) return;
            this.pendingOrders.add(orderId);
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('_method', 'PATCH');
            fetch(this.creditPaidUrl.replace('__ID__', orderId), { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(async response => { const data = await response.json(); if (!response.ok || !data.success) throw new Error(data.message || this.labels.failedOrder); this.showToast('Room credit marked paid'); })
                .catch(error => this.showToast(error.message || this.labels.failedOrder, 'error'))
                .finally(async () => { this.pendingOrders.delete(orderId); if (this.pollPromise) await this.pollPromise; await this.poll(); });
        },

        markRequestCompleted(requestId) {
            const url = this.requestUpdateUrl.replace('__ID__', requestId);
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('_method', 'PATCH');
            formData.append('status', 'completed');

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
            .then(r => {
                if (!r.ok) throw new Error('Server returned ' + r.status);
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    const el = this.$refs.requestsList.querySelector('[data-request-id="' + requestId + '"]');
                    if (el) {
                        el.dataset.status = 'completed';
                        el.classList.remove('border-zem-gold/40', 'bg-zem-gold/10');
                        el.classList.add('border-zem-border', 'bg-zem-card', 'opacity-60');
                        const btn = el.querySelector('button');
                        if (btn) btn.remove();
                        const badge = el.querySelector('[data-status-badge]');
                        if (badge) badge.innerHTML = this.getStatusBadge('completed');
                        this.applyFilter();
                    }
                    this.activeRequests--;
                    this.showToast(this.labels.requestCompleted);
                }
            })
            .catch(() => this.showToast(this.labels.failedRequest, 'error'));
        },

        poll() {
            if (this.polling) return this.pollPromise;
            this.polling = true;
            clearTimeout(this.pollTimer);
            this.updatePollCountdown();
            const headers = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };
            if (this.pollRevision !== null) headers['X-Board-Revision'] = String(this.pollRevision);
            this.pollPromise = fetch(this.pollUrl, {
                cache: 'no-store', signal: AbortSignal.timeout(15000),
                headers,
            })
            .then(async r => {
                if (r.status === 403) { window.location.reload(); return null; }
                if (r.status === 304) return null;
                if (!r.ok) throw new Error('Server returned ' + r.status);
                return { data: await r.json(), revision: r.headers.get('X-Board-Revision') };
            })
            .then(payload => {
                if (!payload) {
                    this.pollError = '';
                    this.pollDelay = 30000;
                    return;
                }
                this.pollRevision = payload.revision || String(payload.data.revision || '');
                const data = payload.data;
                if (!Array.isArray(data.orders) || !Array.isArray(data.requests)) throw new Error('Invalid update');
                this.pollError = '';
                const ids = new Set(data.orders.map(order => String(order.id)));
                this.$refs.ordersList.querySelectorAll('[data-order-id]').forEach(el => {
                    if (!ids.has(el.dataset.orderId)) el.remove();
                });
                let hasNewOrder = false;
                data.orders.forEach(order => {
                    let article = this.$refs.ordersList.querySelector('[data-order-id="' + order.id + '"]');
                    if (!article) {
                        hasNewOrder = hasNewOrder || order.id > this.latestOrderId ||
                            (this.staffRole === 'kitchen' && order.confirmed && order.created_at);
                        this.prependOrder(order);
                        article = this.$refs.ordersList.querySelector('[data-order-id="' + order.id + '"]');
                    }
                    this.$refs.ordersList.appendChild(article);
                });
                this.syncOrderStatuses(data.orders);
                if (!data.orders.length) {
                    this.$refs.ordersList.innerHTML = '<p class="p-8 text-center text-zem-muted">' + this.escapeHtml(@js(__('No orders in this view.'))) + '</p>';
                } else {
                    this.$refs.ordersList.querySelectorAll(':scope > p').forEach(el => el.remove());
                }
                if (this.$refs.pagination) this.$refs.pagination.innerHTML = data.pagination || '';
                if (this.staffRole !== 'kitchen' && this.$refs.requestsList) {
                    this.$refs.requestsList.innerHTML = '';
                    [...data.requests].reverse().forEach(req => this.prependRequest(req));
                }
                this.activeCount = data.activeCount;
                this.completedCount = data.completedCount;
                this.activeRequests = data.activeRequests;
                this.latestOrderId = Number(data.latestOrderId || 0);
                this.latestConfirmedAt = data.latestConfirmedAt;
                this.latestRequestId = Number(data.latestRequestId || 0);
                if (hasNewOrder) this.playBeep();
                this.pollDelay = document.hidden ? 60000 : 30000;
            })
            .catch(() => {
                this.pollError = @js(__('Update failed. Showing last known orders. Retry or refresh.'));
                this.pollDelay = document.hidden ? 60000 : (this.slowConnection() ? 60000 : 30000);
            })
            .finally(() => {
                this.polling = false;
                this.schedulePoll(this.pollDelay);
            });
            return this.pollPromise;
        },

        playBeep() {
            window.zemtabOrderAlerts?.notify();
        },

        prependOrder(order) {
            const list = this.$refs.ordersList;
            const emptyDiv = list.querySelector('div.text-center');
            if (emptyDiv) emptyDiv.remove();

            const article = document.createElement('article');
            const isCompleted = order.status === 'completed';
            article.className = 'rounded-md border-l-4 border border-zem-border bg-zem-card p-4 animate-slide-in ' + (isCompleted ? 'border-l-gray-400 opacity-60' : 'border-l-zem-gold');
            article.dataset.orderId = order.id;
            article.dataset.status = order.status;
            article.dataset.paymentStatus = order.payment_status || '';
            article.dataset.paymentMethod = order.payment_method || '';
            article.dataset.confirmed = order.confirmed ? '1' : '0';
            article.dataset.needsConfirmation = order.needs_confirmation ? '1' : '0';

            let itemsHtml = order.items.map(item =>
                '<p class="flex justify-between gap-3 rounded-md border border-zem-border bg-zem-bg px-3 py-2 text-sm text-zem-cream"><span>' + Number(item.quantity) + ' x ' + this.escapeHtml(item.name) + (item.note ? ' <em class="text-zem-muted">(' + this.escapeHtml(item.note) + ')</em>' : '') + '</span><strong class="shrink-0 text-zem-cream">' + new Intl.NumberFormat('en-US').format(item.total_price) + ' ETB</strong></p>'
            ).join('');

            const statusBadge = this.getStatusBadge(order.status);
            const orderTags = (order.order_type === 'delivery' ? ' <span class="ml-1 rounded-full bg-purple-100 px-2 py-0.5 text-xs font-bold text-purple-700">Driver pickup</span>' : '') + (order.payment_method === 'room_credit' && order.payment_status !== 'paid' ? ' <span class="ml-1 rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-bold text-yellow-700">Room credit · unpaid</span>' : '') + (order.needs_confirmation ? ' <span class="ml-1 rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-bold text-yellow-700">' + this.escapeHtml(this.labels.needsConfirm) + '</span>' : '');

            article.innerHTML =
                '<div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-display text-xl font-bold">' + this.escapeHtml(this.labels.order) + ' #' + order.id + '</h2><p class="text-sm text-zem-muted">' + this.escapeHtml(order.table_label || order.table_number) + orderTags + ' - <span data-created-at="' + this.escapeHtml(order.created_at) + '">' + this.relativeTime(order.created_at) + '</span></p></div><span data-status-badge>' + statusBadge + '</span></div>' +
                '<div class="mt-4 space-y-2">' + itemsHtml + '</div>' +
                (order.note ? '<p class="mt-3 text-sm text-zem-muted">' + this.escapeHtml(this.labels.note) + ': ' + this.escapeHtml(order.note) + '</p>' : '') +
                '<div class="mt-4 flex flex-wrap items-center justify-between gap-3" data-order-actions><strong>' + new Intl.NumberFormat('en-US').format(order.total) + ' ETB</strong></div>';

            list.prepend(article);
            this.renderOrderActions(article, order.id, order.status);
            this.applyFilter();
        },

        renderOrderActions(article, orderId, status) {
            const actions = article.querySelector('[data-order-actions]');
            if (!actions) return;
            const total = actions.querySelector('strong')?.outerHTML || '';
            let control = '';
            const needsConfirmation = article.dataset.needsConfirmation === '1';
            const paymentStatus = article.dataset.paymentStatus || '';
            const paymentMethod = article.dataset.paymentMethod || '';
            if (status === 'completed' && paymentMethod === 'room_credit' && paymentStatus !== 'paid' && ['owner_manager', 'cashier'].includes(this.staffRole)) {
                control = '<span class="rounded-md border border-zem-gold/40 bg-zem-gold/10 px-4 py-3 text-sm font-bold text-zem-gold">Room credit · unpaid</span><button type="button" data-mark-credit-paid class="rounded-md bg-emerald-600 px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]">Mark credit paid</button>';
            } else if (!['completed', 'cancelled'].includes(status)) {
                if (['owner_manager', 'cashier'].includes(this.staffRole) && needsConfirmation) {
                    control = '<button type="button" data-confirm-order class="rounded-md bg-yellow-500 px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]">' + this.escapeHtml(this.labels.confirmOrder) + '</button>';
                } else if (!this.kitchenScreenEnabled && ['owner_manager', 'cashier'].includes(this.staffRole)) {
                    control = '<button type="button" data-mark-completed class="rounded-md bg-zem-green px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]">' + this.escapeHtml(this.labels.markCompleted) + '</button>';
                } else if (['owner_manager', 'cashier'].includes(this.staffRole) && status === 'served') {
                    control = '<div class="flex gap-2"><select id="payment-method-' + orderId + '" class="rounded-md border border-zem-border bg-white px-3 py-3 text-sm"><option value="">Payment method...</option>' + this.paymentMethods.map(method => '<option value="' + this.escapeHtml(method) + '">' + this.escapeHtml(method === 'room_credit' ? 'Room credit (pay later)' : method.charAt(0).toUpperCase() + method.slice(1)) + '</option>').join('') + '</select><button type="button" data-mark-payment class="rounded-md bg-emerald-600 px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]">Apply payment</button></div>';
                } else if (this.staffRole === 'cashier' && status !== 'paid') {
                    control = '<span class="rounded-md border border-zem-border bg-zem-soft px-4 py-3 text-sm font-bold text-zem-muted">Waiting for kitchen</span>';
                } else if (this.staffRole === 'cashier' && status === 'paid') {
                    control = '<button type="button" data-mark-completed class="rounded-md bg-zem-green px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]">' + this.escapeHtml(this.labels.markCompleted) + '</button>';
                } else if (this.staffRole === 'kitchen' && status === 'new') {
                    control = '<button type="button" data-start-preparing class="rounded-md bg-blue-600 px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]">Start Preparing</button>';
                } else if (this.staffRole === 'kitchen' && status === 'preparing') {
                    control = '<button type="button" data-mark-served class="rounded-md bg-green-600 px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]">Mark Served</button>';
                } else if (this.staffRole === 'owner_manager') {
                    control = '<button type="button" data-mark-completed class="rounded-md bg-zem-green px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]">' + this.escapeHtml(this.labels.markCompleted) + '</button>';
                }
            }
            actions.innerHTML = total + control;
            actions.querySelector('[data-confirm-order]')?.addEventListener('click', () => this.confirmOrder(orderId));
            actions.querySelector('[data-mark-paid]')?.addEventListener('click', () => this.markPaid(orderId));
            actions.querySelector('[data-mark-payment]')?.addEventListener('click', () => this.markPayment(orderId));
            actions.querySelector('[data-mark-credit-paid]')?.addEventListener('click', () => this.markCreditPaid(orderId));
            actions.querySelector('[data-mark-completed]')?.addEventListener('click', () => this.markCompleted(orderId));
            actions.querySelector('[data-start-preparing]')?.addEventListener('click', () => this.updateStatus(orderId, 'preparing'));
            actions.querySelector('[data-mark-served]')?.addEventListener('click', () => this.updateStatus(orderId, 'served'));
        },

        prependRequest(req) {
            const list = this.$refs.requestsList;
            if (!list || this.staffRole === 'kitchen') return;
            const emptyDiv = list.querySelector('div.text-center');
            if (emptyDiv) emptyDiv.remove();

            const div = document.createElement('div');
            const isCompleted = req.status === 'completed';
            div.className = 'rounded-md border p-4 animate-slide-in ' + (isCompleted ? 'border-zem-border bg-zem-card opacity-60' : 'border-zem-gold/40 bg-zem-gold/10');
            div.dataset.requestId = req.id;
            div.dataset.status = req.status;

            const label = this.requestTypeLabels[req.type] || req.type;
            const statusBadge = this.getStatusBadge(req.status);
            const completeBtn = isCompleted ? '' : '<button type="button" class="mt-3 w-full rounded-md bg-zem-green px-4 py-2 text-sm font-bold text-white transition hover:opacity-90">' + this.escapeHtml(this.labels.markCompleted) + '</button>';

            div.innerHTML =
                '<div class="flex flex-wrap items-start justify-between gap-3"><div><strong>' + this.escapeHtml(req.table_label || req.table_number) + '</strong><p class="mt-1 text-sm text-zem-muted">' + this.escapeHtml(label) + ' - <span data-created-at="' + this.escapeHtml(req.created_at) + '">' + this.relativeTime(req.created_at) + '</span></p>' + (req.note ? '<p class="mt-2 text-sm">' + this.escapeHtml(req.note) + '</p>' : '') + '</div><span data-status-badge>' + statusBadge + '</span></div>' +
                completeBtn;

            const btn = div.querySelector('button');
            if (btn) {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.markRequestCompleted(req.id);
                });
            }

            list.prepend(div);
            this.applyFilter();
        },

        applyFilter() {
            if (!this.$refs.ordersList) return;
            this.$refs.ordersList.querySelectorAll('[data-order-id]').forEach(el => {
                const completed = this.staffRole === 'kitchen' ? ['served', 'paid', 'completed'].includes(el.dataset.status) : el.dataset.status === 'completed';
                const active = this.staffRole === 'kitchen' ? ['new', 'preparing'].includes(el.dataset.status) : !['completed', 'cancelled'].includes(el.dataset.status);
                el.style.display = this.filter === 'all' || (this.filter === 'completed' && completed) || (this.filter === 'active' && active) ? '' : 'none';
            });
            if (!this.$refs.requestsList) return;
            this.$refs.requestsList.querySelectorAll('[data-request-id]').forEach(el => {
                const completed = el.dataset.status === 'completed';
                el.style.display = this.filter === 'all' || (this.filter === 'completed' && completed) || (this.filter === 'active' && !completed) ? '' : 'none';
            });
        },

        syncOrderStatuses(rows) {
            rows.forEach(row => {
                const article = this.$refs.ordersList.querySelector('[data-order-id="' + row.id + '"]');
                if (!article) return;
                const confirmed = row.confirmed ? '1' : '0';
                const needsConfirmation = row.needs_confirmation ? '1' : '0';
                const changed = article.dataset.status !== row.status ||
                    article.dataset.paymentStatus !== (row.payment_status || '') ||
                    article.dataset.paymentMethod !== (row.payment_method || '') ||
                    article.dataset.confirmed !== confirmed ||
                    article.dataset.needsConfirmation !== needsConfirmation;
                article.dataset.status = row.status;
                article.dataset.paymentStatus = row.payment_status || '';
                article.dataset.paymentMethod = row.payment_method || '';
                article.dataset.confirmed = confirmed;
                article.dataset.needsConfirmation = needsConfirmation;
                const active = !['completed', 'cancelled'].includes(row.status);
                article.classList.toggle('opacity-60', !active);
                article.classList.toggle('border-l-zem-gold', active && !row.needs_confirmation);
                article.classList.toggle('border-l-yellow-400', active && row.needs_confirmation);
                article.classList.toggle('border-l-gray-400', !active);
                if (!row.needs_confirmation) article.querySelectorAll('.bg-yellow-100.text-yellow-700').forEach(el => el.remove());
                if (changed) {
                    const badge = article.querySelector('[data-status-badge]');
                    if (badge) badge.innerHTML = this.getStatusBadge(row.status);
                    // Replace controls, including old Alpine listeners, as one unit.
                    this.renderOrderActions(article, row.id, row.status);
                }
            });
            this.applyFilter();
        },

        escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = String(value ?? '');
            return div.innerHTML;
        },

        relativeTime(timestamp) {
            const elapsed = Math.max(0, Math.floor((Date.now() - new Date(timestamp).getTime()) / 1000));
            if (elapsed < 60) return elapsed + ' ' + (elapsed === 1 ? @js(__('second ago')) : @js(__('seconds ago')));
            const minutes = Math.floor(elapsed / 60);
            if (minutes < 60) return minutes + ' ' + (minutes === 1 ? @js(__('minute ago')) : @js(__('minutes ago')));
            const hours = Math.floor(minutes / 60);
            if (hours < 24) return hours + ' ' + (hours === 1 ? @js(__('hour ago')) : @js(__('hours ago')));
            const days = Math.floor(hours / 24);
            return days + ' ' + (days === 1 ? @js(__('day ago')) : @js(__('days ago')));
        },

        updateRelativeTimes() {
            document.querySelectorAll('[data-created-at]').forEach(el => {
                el.textContent = this.relativeTime(el.dataset.createdAt);
            });
        },

        getStatusBadge(status) {
            const colors = {
                'new': 'bg-zem-gold/20 text-zem-gold border-zem-gold/40',
                'preparing': 'bg-blue-100 text-blue-700 border-blue-300',
                'served': 'bg-green-100 text-green-700 border-green-300',
                'paid': 'bg-emerald-100 text-emerald-700 border-emerald-300',
                'completed': 'bg-gray-100 text-gray-600 border-gray-300',
                'cancelled': 'bg-red-100 text-red-700 border-red-300',
                'pending': 'bg-zem-gold/20 text-zem-gold border-zem-gold/40',
                'acknowledged': 'bg-blue-100 text-blue-700 border-blue-300',
            };
            const cls = colors[status] || 'bg-zem-bg text-zem-muted border-zem-border';
            return '<span class="rounded-full border px-3 py-1 text-xs font-bold ' + cls + '">' + this.escapeHtml(this.statusLabels[status] || status) + '</span>';
        },
    }
}
</script>
@endsection
