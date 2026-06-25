@extends('layouts.dashboard', ['heading' => __('Work Board'), 'eyebrow' => __('Live orders & service requests')])

@section('content')
@include('restaurant.partials.order_sound_alerts', ['latestOrderId' => $latestOrderId])
@php($placeTitle = __($restaurant->locationLabelTitle()))
@php($requestTypeLabels = ['call_waiter' => __($restaurant->staffRequestLabel()), 'request_bill' => __('Request Bill'), 'request_water' => __('Request Water'), 'other' => __('Other')])
@php($staffRole = session('staff_profile_role', 'owner_manager'))
@php($paymentMethods = $paymentMethods ?? ['cash', 'telebirr', 'cbe', 'awash', 'abyssinia'])
<div x-data="workBoard()" x-init="init()" x-cloak>
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div class="grid gap-3 sm:grid-cols-4">
        <div class="rounded-md border border-zem-border bg-zem-card px-4 py-3"><p class="text-xs text-zem-muted">{{ __('Active orders') }}</p><p class="mt-1 text-2xl font-extrabold" x-text="activeCount">{{ $orders->whereNotIn('status', ['completed', 'cancelled'])->count() }}</p></div>
        <div class="rounded-md border border-zem-border bg-zem-card px-4 py-3"><p class="text-xs text-zem-muted">{{ __('Completed today') }}</p><p class="mt-1 text-2xl font-extrabold" x-text="completedCount">{{ $orders->where('status', 'completed')->count() }}</p></div>
        <div class="rounded-md border border-zem-border bg-zem-card px-4 py-3"><p class="text-xs text-zem-muted">{{ __('Active requests') }}</p><p class="mt-1 text-2xl font-extrabold" x-text="activeRequests">{{ $activeRequests }}</p></div>
        <div class="rounded-md border border-zem-border bg-zem-card px-4 py-3"><p class="text-xs text-zem-muted">{{ __('Updated') }}</p><p class="mt-1 text-sm font-bold" x-text="updatedTime">{{ now()->format('H:i:s') }}</p></div>
    </div>
</div>

<div class="grid gap-5 xl:grid-cols-[minmax(0,1.5fr)_minmax(360px,.9fr)]">
    <section>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-display text-xl font-bold">{{ __('Orders') }}</h2>
            <div class="flex gap-2">
                <button @click="filter='active'; applyFilter()" :class="filter==='active'?'bg-zem-gold text-white':'border border-zem-border'" class="rounded-full px-4 py-2 text-sm font-bold">{{ __('Active') }}</button>
                <button @click="filter='completed'; applyFilter()" :class="filter==='completed'?'bg-zem-gold text-white':'border border-zem-border'" class="rounded-full px-4 py-2 text-sm font-bold">{{ __('Completed') }}</button>
                <button @click="filter='all'; applyFilter()" :class="filter==='all'?'bg-zem-gold text-white':'border border-zem-border'" class="rounded-full px-4 py-2 text-sm font-bold">{{ __('All') }}</button>
            </div>
        </div>
        <div class="grid gap-4" x-ref="ordersList">
            @forelse($orders as $order)
                <article class="rounded-md border-l-4 border border-zem-border bg-zem-card p-4 {{ in_array($order->status, ['completed', 'cancelled'], true) ? 'border-l-gray-400 opacity-60' : 'border-l-zem-gold' }}" data-order-id="{{ $order->id }}" data-status="{{ $order->status }}" x-show="filter==='all' || (filter==='active' && !['completed','cancelled'].includes('{{ $order->status }}')) || (filter==='completed' && '{{ $order->status }}' === 'completed')">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><h2 class="font-display text-xl font-bold">{{ __('Order') }} #{{ $order->id }}</h2><p class="text-sm text-zem-muted">{{ $placeTitle }} {{ $order->table_number }}@if(($order->order_type ?? 'dine_in') === 'delivery') <span class="ml-1 rounded-full bg-purple-100 px-2 py-0.5 text-xs font-bold text-purple-700">Delivery</span>@endif - <span data-created-at="{{ $order->created_at->toIso8601String() }}">{{ $order->created_at->diffForHumans() }}</span></p></div>
                        <span data-status-badge><x-status :status="$order->status" /></span>
                    </div>
                    <div class="mt-4 space-y-2">
                        @foreach($order->items as $item)<p class="flex justify-between gap-3 rounded-md border border-zem-border bg-zem-bg px-3 py-2 text-sm text-zem-cream"><span>{{ $item->quantity }} x {{ $item->item_name }} @if($item->note)<em class="text-zem-muted">({{ $item->note }})</em>@endif</span><strong class="shrink-0 text-zem-cream">{{ number_format($item->total_price) }} ETB</strong></p>@endforeach
                    </div>
                    <p class="mt-3 text-sm text-zem-muted">{{ __('Note') }}: {{ $order->note ?: __('None') }}</p>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <strong>{{ number_format($order->total) }} ETB</strong>
                        @if(!in_array($order->status, ['completed', 'cancelled'], true))
                            @if($staffRole === 'cashier' && !in_array($order->status, ['paid']))
                                <div class="flex gap-2">
                                    <select id="payment-method-{{ $order->id }}" class="rounded-md border border-zem-border bg-white px-3 py-3 text-sm">
                                        <option value="">Payment method...</option>
                                        @foreach($paymentMethods as $method)
                                            <option value="{{ $method }}">{{ ucfirst($method) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" @click="markPaid({{ $order->id }})" class="rounded-md bg-emerald-600 px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]">Mark Paid</button>
                                </div>
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
                    <div class="text-4xl mb-2">🍽️</div>
                    <p class="text-zem-muted">No orders yet. Scan the QR code at a {{ $restaurant->locationLabel() }} to start.</p>
                    <a href="{{ route('restaurant.tables.index') }}" class="mt-3 inline-block rounded-md border border-zem-gold px-4 py-2 text-sm font-bold text-zem-gold">Go to QR codes</a>
                </div>
            @endforelse
        </div>
        <div class="mt-5">{{ $orders->links() }}</div>
    </section>

    <aside>
        <div class="sticky top-4">
            @if(in_array($staffRole, ['owner_manager', 'cashier'], true))
                <div class="mb-5 rounded-md border border-zem-border bg-zem-card p-4" x-data="{ items: [], selected: '', qty: 1, note: '' }">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-display text-xl font-bold">{{ __('Manual Order') }}</h2>
                    </div>
                    <form method="post" action="{{ route('restaurant.orders.manual.store') }}" class="mt-4 space-y-3" @submit="if (items.length === 0) { $event.preventDefault(); showToast('Add at least one item', 'error'); }">
                        @csrf
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="grid gap-1 text-sm">
                                <span class="font-bold">{{ $placeTitle }}</span>
                                <input name="table_number" list="manual-order-tables" required placeholder="{{ __('Table or takeaway') }}" class="rounded-md border border-zem-border bg-white px-3 py-2">
                                <datalist id="manual-order-tables">
                                    @foreach($tables as $table)
                                        <option value="{{ $table->table_number }}">{{ $table->table_name ?: $table->table_number }}</option>
                                    @endforeach
                                    <option value="Takeaway">{{ __('Takeaway') }}</option>
                                </datalist>
                            </label>
                            <label class="grid gap-1 text-sm">
                                <span class="font-bold">{{ __('Customer') }}</span>
                                <input name="customer_name" placeholder="{{ __('Optional') }}" class="rounded-md border border-zem-border bg-white px-3 py-2">
                            </label>
                        </div>

                        <div class="rounded-md border border-zem-border bg-zem-bg p-3">
                            <div class="grid gap-2 sm:grid-cols-[1fr_86px_auto]">
                                <select x-model="selected" class="rounded-md border border-zem-border bg-white px-3 py-2 text-sm">
                                    <option value="">{{ __('Select menu item') }}...</option>
                                    @foreach($menuItems as $item)
                                        <option value="{{ $item->id }}" data-name="{{ $item->name }}" data-price="{{ $item->price }}">{{ $item->name }} - {{ number_format($item->price) }} ETB</option>
                                    @endforeach
                                </select>
                                <input type="number" x-model.number="qty" min="1" max="50" class="rounded-md border border-zem-border bg-white px-3 py-2 text-sm" aria-label="{{ __('Quantity') }}">
                                <button type="button" class="rounded-md bg-zem-gold px-4 py-2 text-sm font-bold text-white" @click="
                                    const option = $el.parentElement.querySelector('select').selectedOptions[0];
                                    if (! selected || ! option) return;
                                    items.push({ id: selected, name: option.dataset.name, qty: qty || 1, note: '' });
                                    selected = '';
                                    qty = 1;
                                ">{{ __('Add') }}</button>
                            </div>
                            <div class="mt-3 space-y-2">
                                <template x-for="(item, index) in items" :key="index">
                                    <div class="rounded-md border border-zem-border bg-zem-card p-2">
                                        <div class="flex items-center justify-between gap-2 text-sm">
                                            <span class="font-bold" x-text="item.qty + ' x ' + item.name"></span>
                                            <button type="button" @click="items.splice(index, 1)" class="text-sm font-bold text-red-500">{{ __('Remove') }}</button>
                                        </div>
                                        <input type="text" x-model="item.note" placeholder="{{ __('Item note') }}" class="mt-2 w-full rounded-md border border-zem-border bg-white px-3 py-2 text-sm">
                                        <input type="hidden" :name="'items[' + index + '][id]'" :value="item.id">
                                        <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.qty">
                                        <input type="hidden" :name="'items[' + index + '][note]'" :value="item.note">
                                    </div>
                                </template>
                            </div>
                        </div>

                        <textarea name="note" rows="2" placeholder="{{ __('Order note') }}" class="w-full rounded-md border border-zem-border bg-white px-3 py-2 text-sm"></textarea>
                        <button class="w-full rounded-md bg-zem-gold px-4 py-3 text-base font-bold text-white">{{ __('Create Manual Order') }}</button>
                    </form>
                </div>
            @endif
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-display text-xl font-bold">{{ __('Service Requests') }}</h2>
            </div>
            <div class="grid gap-3" x-ref="requestsList">
                @forelse($requests as $requestRow)
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
                    <div class="rounded-md border border-zem-border bg-zem-card p-6 text-center">
                        <div class="text-3xl mb-2">🔔</div>
                        <p class="text-zem-muted text-sm">{{ __('No service requests yet.') }}</p>
                    </div>
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
function workBoard() {
    return {
        filter: 'active',
        activeCount: 0,
        completedCount: 0,
        activeRequests: 0,
        updatedTime: '',
        toast: false,
        toastMessage: '',
        toastType: 'success',
        latestOrderId: 0,
        latestRequestId: 0,
        pollTimer: null,
        pollDelay: 15000,
        idlePolls: 0,
        polling: false,
        pollUrl: '{{ route("restaurant.orders.poll") }}',
        orderUpdateUrl: '{{ route("restaurant.orders.update", ["__ID__"]) }}',
        requestUpdateUrl: '{{ route("restaurant.service-requests.update", ["__ID__"]) }}',
        requestTypeLabels: @js($requestTypeLabels),
        placeTitle: @js($placeTitle),
        labels: @js([
            'order' => __('Order'), 'note' => __('Note'), 'none' => __('None'),
            'markCompleted' => __('Mark as completed'), 'requestCompleted' => __('Request completed'),
            'failedRequest' => __('Failed to update request'), 'failedOrder' => __('Failed to update order'),
        ]),
        statusLabels: @js(collect(['new','preparing','served','paid','completed','cancelled','pending','acknowledged'])->mapWithKeys(fn ($status) => [$status => __(ucfirst($status))])),

        init() {
            this.latestOrderId = {{ $latestOrderId }};
            this.latestRequestId = {{ $latestRequestId }};
            this.activeRequests = {{ $activeRequests }};
            this.activeCount = {{ $orders->whereNotIn('status', ['completed', 'cancelled'])->count() }};
            this.completedCount = {{ $orders->where('status', 'completed')->count() }};
            this.updatedTime = '{{ now()->format("H:i:s") }}';

            this.schedulePoll(15000);
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.schedulePoll(60000);
                    return;
                }
                this.pollDelay = 15000;
                this.idlePolls = 0;
                this.poll();
            });
            this.updateRelativeTimes();
            setInterval(() => this.updateRelativeTimes(), 1000);
        },

        schedulePoll(delay = this.pollDelay) {
            clearTimeout(this.pollTimer);
            this.pollTimer = setTimeout(() => this.poll(), delay);
        },

        showToast(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;
            this.toast = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => this.toast = false, 3000);
        },

        markPaid(orderId) {
            const select = document.getElementById('payment-method-' + orderId);
            const paymentMethod = select ? select.value : '';
            if (!paymentMethod) { this.showToast('Select a payment method first', 'error'); return; }
            const url = this.orderUpdateUrl.replace('__ID__', orderId);
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('_method', 'PATCH');
            formData.append('status', 'paid');
            formData.append('payment_method', paymentMethod);
            fetch(url, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(r => { if (!r.ok) throw new Error('Server returned ' + r.status); return r.json(); })
            .then(data => {
                if (data.success) {
                    const article = this.$refs.ordersList.querySelector('[data-order-id="' + orderId + '"]');
                    if (article) {
                        article.dataset.status = 'paid';
                        const badge = article.querySelector('[data-status-badge]');
                        if (badge) badge.innerHTML = this.getStatusBadge('paid');
                        const selectEl = article.querySelector('select');
                        const paidBtn = article.querySelector('button.bg-emerald-600');
                        if (selectEl) selectEl.remove();
                        if (paidBtn) {
                            paidBtn.textContent = this.labels.markCompleted;
                            paidBtn.className = 'rounded-md bg-zem-green px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]';
                            paidBtn.onclick = () => this.markCompleted(orderId);
                        }
                        this.applyFilter();
                    }
                    this.showToast('Order #' + orderId + ' marked as paid (' + paymentMethod + ')');
                }
            })
            .catch(() => this.showToast('Failed to mark order #' + orderId + ' as paid', 'error'));
        },

        updateStatus(orderId, status) {
            const url = this.orderUpdateUrl.replace('__ID__', orderId);
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('_method', 'PATCH');
            formData.append('status', status);
            fetch(url, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(r => { if (!r.ok) throw new Error('Server returned ' + r.status); return r.json(); })
            .then(data => {
                if (data.success) {
                    const article = this.$refs.ordersList.querySelector('[data-order-id="' + orderId + '"]');
                    if (article) {
                        article.dataset.status = status;
                        const badge = article.querySelector('[data-status-badge]');
                        if (badge) badge.innerHTML = this.getStatusBadge(status);
                        const btn = article.querySelector('button');
                        if (btn) {
                            if (status === 'preparing') {
                                btn.textContent = 'Mark Served';
                                btn.className = 'rounded-md bg-green-600 px-6 py-3 text-base font-bold text-white transition hover:opacity-90 min-h-[56px]';
                                btn.onclick = () => this.updateStatus(orderId, 'served');
                            } else if (status === 'served') {
                                btn.remove();
                            }
                        }
                        this.applyFilter();
                    }
                    this.showToast('Order #' + orderId + ' status: ' + status);
                }
            })
            .catch(() => this.showToast('Failed to update order #' + orderId, 'error'));
        },

        markCompleted(orderId) {
            const url = this.orderUpdateUrl.replace('__ID__', orderId);
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
                    const article = this.$refs.ordersList.querySelector('[data-order-id="' + orderId + '"]');
                    if (article) {
                        article.dataset.status = 'completed';
                        article.classList.remove('border-l-zem-gold');
                        article.classList.add('border-l-gray-400', 'opacity-60');
                        const btn = article.querySelector('button');
                        if (btn) btn.remove();
                        const badge = article.querySelector('[data-status-badge]');
                        if (badge) badge.innerHTML = this.getStatusBadge('completed');
                        this.activeCount--;
                        this.completedCount++;
                        this.applyFilter();
                    }
                    this.showToast(this.labels.order + ' #' + orderId + ' ' + @js(__('Completed')));
                }
            })
            .catch(() => this.showToast(this.labels.failedOrder + ' #' + orderId, 'error'));
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
            if (this.polling) return;
            this.polling = true;
            const params = new URLSearchParams({
                order_since: this.latestOrderId,
                request_since: this.latestRequestId,
            });
            this.$refs.ordersList.querySelectorAll('[data-order-id]').forEach(el => params.append('visible_order_ids[]', el.dataset.orderId));
            fetch(this.pollUrl + '?' + params.toString(), {
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
            .then(r => {
                if (!r.ok) return { orders: [], requests: [], activeRequests: this.activeRequests, latestOrderId: this.latestOrderId, latestRequestId: this.latestRequestId };
                return r.json();
            })
            .then(data => {
                this.updatedTime = new Date().toLocaleTimeString('en-GB');
                if (typeof data.activeRequests === 'number') this.activeRequests = data.activeRequests;
                const changed = Boolean(data.hasChanges) || (data.orders || []).length > 0 || (data.requests || []).length > 0;

                if (data.orders.length > 0) {
                    [...data.orders].sort((a, b) => a.id - b.id).forEach(order => {
                        if (this.$refs.ordersList.querySelector('[data-order-id="' + order.id + '"]')) return;
                        if (order.status !== 'completed' && order.status !== 'cancelled') this.activeCount++;
                        else if (order.status === 'completed') this.completedCount++;
                        this.prependOrder(order);
                    });
                    if (localStorage.getItem('zemtabOrderSoundEnabled') === '1') this.playBeep();
                }

                if (data.requests.length > 0) {
                    [...data.requests].sort((a, b) => a.id - b.id).forEach(req => {
                        if (!this.$refs.requestsList.querySelector('[data-request-id="' + req.id + '"]')) this.prependRequest(req);
                    });
                }
                this.latestOrderId = Math.max(this.latestOrderId, Number(data.latestOrderId || 0));
                this.latestRequestId = Math.max(this.latestRequestId, Number(data.latestRequestId || 0));
                this.syncOrderStatuses(data.orderStatuses || []);
                this.idlePolls = changed ? 0 : this.idlePolls + 1;
                this.pollDelay = document.hidden ? 60000 : (this.idlePolls > 12 ? 45000 : (this.idlePolls > 3 ? 25000 : 15000));
            })
            .catch(() => {
                this.pollDelay = document.hidden ? 60000 : 45000;
            })
            .finally(() => {
                this.polling = false;
                this.schedulePoll(this.pollDelay);
            });
        },

        playBeep() {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const context = new AudioContext();
            const oscillator = context.createOscillator();
            const gain = context.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.value = 880;
            gain.gain.setValueAtTime(0.001, context.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.22, context.currentTime + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.001, context.currentTime + 0.45);
            oscillator.connect(gain);
            gain.connect(context.destination);
            oscillator.start();
            oscillator.stop(context.currentTime + 0.5);
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

            let itemsHtml = order.items.map(item =>
                '<p class="flex justify-between gap-3 rounded-md border border-zem-border bg-zem-bg px-3 py-2 text-sm text-zem-cream"><span>' + Number(item.quantity) + ' x ' + this.escapeHtml(item.name) + (item.note ? ' <em class="text-zem-muted">(' + this.escapeHtml(item.note) + ')</em>' : '') + '</span><strong class="shrink-0 text-zem-cream">' + new Intl.NumberFormat('en-US').format(item.total_price) + ' ETB</strong></p>'
            ).join('');

            const statusBadge = this.getStatusBadge(order.status);
            const completeBtn = isCompleted ? '' : '<button type="button" onclick="this.dispatchEvent(new CustomEvent(\'mark-completed\', {bubbles: true}))" class="rounded-md bg-zem-green px-4 py-2 text-sm font-bold text-white transition hover:opacity-90">' + this.escapeHtml(this.labels.markCompleted) + '</button>';

            article.innerHTML =
                '<div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-display text-xl font-bold">' + this.escapeHtml(this.labels.order) + ' #' + order.id + '</h2><p class="text-sm text-zem-muted">' + this.escapeHtml(this.placeTitle) + ' ' + this.escapeHtml(order.table_number) + (order.order_type === 'delivery' ? ' <span class="ml-1 rounded-full bg-purple-100 px-2 py-0.5 text-xs font-bold text-purple-700">Delivery</span>' : '') + ' - <span data-created-at="' + this.escapeHtml(order.created_at) + '">' + this.relativeTime(order.created_at) + '</span></p></div><span data-status-badge>' + statusBadge + '</span></div>' +
                '<div class="mt-4 space-y-2">' + itemsHtml + '</div>' +
                '<p class="mt-3 text-sm text-zem-muted">' + this.escapeHtml(this.labels.note) + ': ' + this.escapeHtml(order.note || this.labels.none) + '</p>' +
                '<div class="mt-4 flex flex-wrap items-center justify-between gap-3"><strong>' + new Intl.NumberFormat('en-US').format(order.total) + ' ETB</strong>' + completeBtn + '</div>';

            const btn = article.querySelector('button');
            if (btn) {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.markCompleted(order.id);
                });
            }

            list.prepend(article);
            this.applyFilter();
        },

        prependRequest(req) {
            const list = this.$refs.requestsList;
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
                '<div class="flex flex-wrap items-start justify-between gap-3"><div><strong>' + this.escapeHtml(this.placeTitle) + ' ' + this.escapeHtml(req.table_number) + '</strong><p class="mt-1 text-sm text-zem-muted">' + this.escapeHtml(label) + ' - <span data-created-at="' + this.escapeHtml(req.created_at) + '">' + this.relativeTime(req.created_at) + '</span></p>' + (req.note ? '<p class="mt-2 text-sm">' + this.escapeHtml(req.note) + '</p>' : '') + '</div><span data-status-badge>' + statusBadge + '</span></div>' +
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
                const completed = el.dataset.status === 'completed';
                const active = !['completed', 'cancelled'].includes(el.dataset.status);
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
                if (!article || article.dataset.status === row.status) return;
                const wasActive = !['completed', 'cancelled'].includes(article.dataset.status);
                const isActive = !['completed', 'cancelled'].includes(row.status);
                if (wasActive && !isActive) this.activeCount = Math.max(0, this.activeCount - 1);
                if (row.status === 'completed') this.completedCount++;
                article.dataset.status = row.status;
                article.classList.toggle('opacity-60', !isActive);
                article.classList.toggle('border-l-zem-gold', isActive);
                article.classList.toggle('border-l-gray-400', !isActive);
                const badge = article.querySelector('[data-status-badge]');
                if (badge) badge.innerHTML = this.getStatusBadge(row.status);
                if (!isActive) article.querySelector('button')?.remove();
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
// Order timer - update elapsed time every second
    function updateOrderTimers() {
        document.querySelectorAll('[data-order-timer]').forEach(el => {
            const orderTime = parseInt(el.dataset.orderTime);
            const elapsed = Math.floor((Date.now() / 1000) - orderTime);
            const mins = Math.floor(elapsed / 60);
            const secs = elapsed % 60;
            const timeStr = mins + ':' + secs.toString().padStart(2, '0');

            if (mins < 5) {
                el.className = 'mt-1 text-xs font-bold text-zem-green';
            } else if (mins < 10) {
                el.className = 'mt-1 text-xs font-bold text-zem-gold';
            } else {
                el.className = 'mt-1 text-xs font-bold text-red-500';
            }
            el.textContent = '⏱ ' + timeStr + ' ago';
        });
    }
    updateOrderTimers();
    setInterval(updateOrderTimers, 1000);

    // Add timer to dynamically prepended orders
    const originalPrependOrder = this.prependOrder;
    // (Timer will be added by the existing code via data-order-time)
</script>
@endsection
