@extends('layouts.dashboard', ['heading' => 'Work Board', 'eyebrow' => 'Live orders & service requests'])

@section('content')
@include('restaurant.partials.order_sound_alerts', ['latestOrderId' => $latestOrderId])
@php($placeTitle = $restaurant->locationLabelTitle())
@php($requestTypeLabels = ['call_waiter' => $restaurant->staffRequestLabel(), 'request_bill' => 'Request Bill'])
<div x-data="workBoard()" x-init="init()" x-cloak class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div class="grid gap-3 sm:grid-cols-4">
        <div class="rounded-md border border-zem-border bg-zem-card px-4 py-3"><p class="text-xs text-zem-muted">Today orders</p><p class="mt-1 text-2xl font-extrabold" x-text="todayOrdersCount">{{ $todayOrdersCount }}</p></div>
        <div class="rounded-md border border-zem-border bg-zem-card px-4 py-3"><p class="text-xs text-zem-muted">Today revenue</p><p class="mt-1 text-sm font-extrabold" x-text="todayRevenueText">{{ number_format($todayRevenue) }} ETB</p></div>
        <div class="rounded-md border border-zem-border bg-zem-card px-4 py-3"><p class="text-xs text-zem-muted">Active requests</p><p class="mt-1 text-2xl font-extrabold" x-text="activeRequests">{{ $activeRequests }}</p></div>
        <div class="rounded-md border border-zem-border bg-zem-card px-4 py-3"><p class="text-xs text-zem-muted">Updated</p><p class="mt-1 text-sm font-bold" x-text="updatedTime">{{ now()->format('H:i:s') }}</p></div>
    </div>
    <div class="flex flex-wrap gap-2">
        <button @click="filter='all'" :class="filter==='all'?'bg-zem-gold text-white':'border border-zem-border'" class="rounded-full px-4 py-2 text-sm font-bold">All <span x-text="countByFilter('all')"></span></button>
        <button @click="filter='new'" :class="filter==='new'?'bg-zem-gold text-white':'border border-zem-border'" class="rounded-full px-4 py-2 text-sm font-bold">New <span x-text="countByFilter('new')"></span></button>
        <button @click="filter='preparing'" :class="filter==='preparing'?'bg-zem-gold text-white':'border border-zem-border'" class="rounded-full px-4 py-2 text-sm font-bold">Preparing <span x-text="countByFilter('preparing')"></span></button>
        <button @click="filter='served'" :class="filter==='served'?'bg-zem-gold text-white':'border border-zem-border'" class="rounded-full px-4 py-2 text-sm font-bold">Served <span x-text="countByFilter('served')"></span></button>
        <button @click="filter='completed'" :class="filter==='completed'?'bg-zem-gold text-white':'border border-zem-border'" class="rounded-full px-4 py-2 text-sm font-bold">Completed <span x-text="countByFilter('completed')"></span></button>
    </div>
</div>

<div class="grid gap-5 xl:grid-cols-[minmax(0,1.5fr)_minmax(360px,.9fr)]">
    <section>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-display text-xl font-bold">Orders</h2>
            <span class="text-sm text-zem-muted">{{ $placeTitle }} orders</span>
        </div>
        <div class="grid gap-4" x-ref="ordersList">
            @forelse($orders as $order)
                <article class="rounded-md border-l-4 border border-zem-border bg-zem-card p-4 {{ $order->status === 'new' ? 'border-l-zem-gold' : ($order->status === 'preparing' ? 'border-l-blue-500' : ($order->status === 'served' ? 'border-l-green-500' : ($order->status === 'completed' ? 'border-l-gray-400' : 'border-l-zem-border'))) }}" data-order-id="{{ $order->id }}" data-status="{{ $order->status }}">
                    <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-display text-xl font-bold">Order #{{ $order->id }}</h2><p class="text-sm text-zem-muted">{{ $placeTitle }} {{ $order->table_number }} - {{ $order->created_at->diffForHumans() }}</p></div><x-status :status="$order->status" /></div>
                    <div class="mt-4 space-y-2">
                        @foreach($order->items as $item)<p class="flex justify-between gap-3 rounded-md border border-zem-border bg-zem-bg px-3 py-2 text-sm text-zem-cream"><span>{{ $item->quantity }} x {{ $item->item_name }} @if($item->note)<em class="text-zem-muted">({{ $item->note }})</em>@endif</span><strong class="shrink-0 text-zem-cream">{{ number_format($item->total_price) }} ETB</strong></p>@endforeach
                    </div>
                    <p class="mt-3 text-sm text-zem-muted">Note: {{ $order->note ?: 'None' }}</p>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3"><strong>{{ number_format($order->total) }} ETB</strong><form method="post" action="{{ route('restaurant.orders.update', $order) }}" class="flex gap-2" @submit.prevent="updateStatus($event, {{ $order->id }})">@csrf @method('PATCH')<select name="status" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2 text-zem-cream" @change="updateStatus($event, {{ $order->id }})">@foreach($statuses as $status)<option value="{{ $status }}" @selected($order->status===$status)>{{ $status }}</option>@endforeach</select></form></div>
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
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-display text-xl font-bold">Service requests</h2>
                <span class="text-sm text-zem-muted">Pending first</span>
            </div>
            <div class="grid gap-3" x-ref="requestsList">
                @forelse($requests as $requestRow)
                    <form method="post" action="{{ route('restaurant.service-requests.update', $requestRow) }}" class="grid gap-3 rounded-md border {{ in_array($requestRow->status, ['pending', 'acknowledged'], true) ? 'border-zem-gold/40 bg-zem-gold/10' : 'border-zem-border bg-zem-card' }} p-4" @submit.prevent="updateRequest($event, {{ $requestRow->id }})">
                        @csrf @method('PATCH')
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <strong>{{ $placeTitle }} {{ $requestRow->table_number }}</strong>
                                <p class="mt-1 text-sm text-zem-muted" data-request-type>{{ $restaurant->requestTypeLabel($requestRow->type) }} - {{ $requestRow->created_at->diffForHumans() }}</p>
                                @if($requestRow->note)<p class="mt-2 text-sm">{{ $requestRow->note }}</p>@endif
                            </div>
                            <x-status :status="$requestRow->status" />
                        </div>
                        <div class="flex gap-2">
                            <select name="status" class="min-w-0 flex-1 rounded-md border border-zem-border bg-zem-bg px-3 py-3 text-zem-cream" @change="updateRequest($event, {{ $requestRow->id }})">
                                @foreach(['pending','acknowledged','completed'] as $status)
                                    <option value="{{ $status }}" @selected($requestRow->status===$status)>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                @empty
                    <div class="rounded-md border border-zem-border bg-zem-card p-6 text-center">
                        <div class="text-3xl mb-2">🔔</div>
                        <p class="text-zem-muted text-sm">No service requests yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </aside>
</div>

<div x-show="toast" x-cloak x-transition class="fixed bottom-4 right-4 z-50 rounded-lg border border-zem-border bg-zem-card px-4 py-3 text-sm font-bold shadow-2xl" :class="toastType === 'error' ? 'border-red-400 text-red-700' : 'border-zem-green/40 text-zem-cream'">
    <span x-text="toastMessage"></span>
</div>

<script>
function workBoard() {
    return {
        filter: 'all',
        todayOrdersCount: 0,
        todayRevenue: 0,
        activeRequests: 0,
        updatedTime: '',
        toast: false,
        toastMessage: '',
        toastType: 'success',
        latestOrderId: 0,
        latestRequestId: 0,
        pollUrl: '{{ route("restaurant.orders.poll") }}',
        orderUpdateUrl: '{{ route("restaurant.orders.update", ["__ID__"]) }}',
        requestUpdateUrl: '{{ route("restaurant.service-requests.update", ["__ID__"]) }}',
        requestTypeLabels: {{ json_encode($requestTypeLabels) }},
        placeTitle: '{{ $placeTitle }}',
        statuses: {{ json_encode($statuses) }},

        init() {
            this.latestOrderId = {{ $latestOrderId }};
            this.latestRequestId = {{ $requests->first()?->id ?? 0 }};
            this.todayOrdersCount = {{ $todayOrdersCount }};
            this.todayRevenue = {{ $todayRevenue }};
            this.activeRequests = {{ $activeRequests }};
            this.updatedTime = '{{ now()->format("H:i:s") }}';

            setInterval(() => this.poll(), 4000);
        },

        get todayRevenueText() {
            return new Intl.NumberFormat('en-US').format(this.todayRevenue) + ' ETB';
        },

        showToast(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;
            this.toast = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => this.toast = false, 3000);
        },

        countByFilter(filter) {
            const cards = this.$refs.ordersList.querySelectorAll('[data-order-id]');
            let count = 0;
            cards.forEach(card => {
                if (filter === 'all' || card.dataset.status === filter) count++;
            });
            return count;
        },

        updateStatus(event, orderId) {
            const form = event.target.closest('form');
            const select = form.querySelector('select[name="status"]');
            const newStatus = select.value;
            const url = this.orderUpdateUrl.replace('__ID__', orderId);
            const formData = new FormData(form);

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const article = this.$refs.ordersList.querySelector('[data-order-id="' + orderId + '"]');
                    if (article) {
                        article.dataset.status = data.status;
                        article.className = article.className.replace(/border-l-\S+/g, '').replace('border-l-4', 'border-l-4');
                        article.classList.add('border-l-4', 'border');
                        const borderColor = this.getStatusBorderColor(data.status);
                        article.classList.add(borderColor);
                    }
                    this.showToast('Order #' + orderId + ' → ' + data.status);
                }
            })
            .catch(() => this.showToast('Failed to update order #' + orderId, 'error'));
        },

        updateRequest(event, requestId) {
            const form = event.target.closest('form');
            const select = form.querySelector('select[name="status"]');
            const newStatus = select.value;
            const url = this.requestUpdateUrl.replace('__ID__', requestId);
            const formData = new FormData(form);

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.showToast('Request updated → ' + data.status);
                }
            })
            .catch(() => this.showToast('Failed to update request', 'error'));
        },

        getStatusBorderColor(status) {
            const map = {
                'new': 'border-l-zem-gold',
                'preparing': 'border-l-blue-500',
                'served': 'border-l-green-500',
                'paid': 'border-l-emerald-500',
                'completed': 'border-l-gray-400',
                'cancelled': 'border-l-red-500',
            };
            return map[status] || 'border-l-zem-border';
        },

        poll() {
            const since = Math.max(this.latestOrderId, this.latestRequestId);
            fetch(this.pollUrl + '?since=' + since, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
            .then(r => r.json())
            .then(data => {
                this.updatedTime = new Date().toLocaleTimeString('en-GB');
                this.activeRequests = data.activeRequests;

                if (data.orders.length > 0) {
                    data.orders.forEach(order => {
                        if (order.id > this.latestOrderId) {
                            this.latestOrderId = order.id;
                            this.todayOrdersCount++;
                            this.todayRevenue += order.total;
                            this.prependOrder(order);
                            if (localStorage.getItem('zemtabOrderSoundEnabled') === '1') {
                                this.playBeep();
                            }
                        }
                    });
                }

                if (data.requests.length > 0) {
                    data.requests.forEach(req => {
                        if (req.id > this.latestRequestId) {
                            this.latestRequestId = req.id;
                            this.prependRequest(req);
                        }
                    });
                }
            })
            .catch(() => {});
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
            article.className = 'rounded-md border-l-4 border border-zem-border bg-zem-card p-4 animate-slide-in border-l-zem-gold';
            article.dataset.orderId = order.id;
            article.dataset.status = order.status;

            let itemsHtml = order.items.map(item =>
                '<p class="flex justify-between gap-3 rounded-md border border-zem-border bg-zem-bg px-3 py-2 text-sm text-zem-cream"><span>' + item.quantity + ' x ' + item.name + (item.note ? '<em class="text-zem-muted">(' + item.note + ')</em>' : '') + '</span><strong class="shrink-0 text-zem-cream">' + new Intl.NumberFormat('en-US').format(item.total_price) + ' ETB</strong></p>'
            ).join('');

            const statusBadge = this.getStatusBadge(order.status);

            article.innerHTML =
                '<div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-display text-xl font-bold">Order #' + order.id + '</h2><p class="text-sm text-zem-muted">' + this.placeTitle + ' ' + order.table_number + ' - ' + order.created_at + '</p></div>' + statusBadge + '</div>' +
                '<div class="mt-4 space-y-2">' + itemsHtml + '</div>' +
                '<p class="mt-3 text-sm text-zem-muted">Note: ' + (order.note || 'None') + '</p>' +
                '<div class="mt-4 flex flex-wrap items-center justify-between gap-3"><strong>' + new Intl.NumberFormat('en-US').format(order.total) + ' ETB</strong><form method="post" class="flex gap-2">@csrf @method("PATCH")<select name="status" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2 text-zem-cream">' + this.statuses.map(s => '<option value="' + s + '"' + (s === order.status ? ' selected' : '') + '>' + s + '</option>').join('') + '</select></form></div>';

            // Add event listener for status changes
            const select = article.querySelector('select[name="status"]');
            select.addEventListener('change', (e) => {
                this.updateStatus(e, order.id);
            });

            list.prepend(article);
        },

        prependRequest(req) {
            const list = this.$refs.requestsList;
            const emptyDiv = list.querySelector('div.text-center');
            if (emptyDiv) emptyDiv.remove();

            const form = document.createElement('form');
            form.className = 'grid gap-3 rounded-md border border-zem-gold/40 bg-zem-gold/10 p-4 animate-slide-in';
            form.dataset.requestId = req.id;

            const label = this.requestTypeLabels[req.type] || req.type;
            const statusBadge = this.getStatusBadge(req.status);

            form.innerHTML =
                '<div class="flex flex-wrap items-start justify-between gap-3"><div><strong>' + this.placeTitle + ' ' + req.table_number + '</strong><p class="mt-1 text-sm text-zem-muted">' + label + ' - ' + req.created_at + '</p>' + (req.note ? '<p class="mt-2 text-sm">' + req.note + '</p>' : '') + '</div>' + statusBadge + '</div>' +
                '<div class="flex gap-2"><select name="status" class="min-w-0 flex-1 rounded-md border border-zem-border bg-zem-bg px-3 py-3 text-zem-cream">' + ['pending','acknowledged','completed'].map(s => '<option value="' + s + '"' + (s === req.status ? ' selected' : '') + '>' + s + '</option>').join('') + '</select></div>';

            const select = form.querySelector('select[name="status"]');
            select.addEventListener('change', (e) => {
                this.updateRequest(e, req.id);
            });

            list.prepend(form);
        },

        getStatusBadge(status) {
            const colors = {
                'new': 'bg-zem-gold/20 text-zem-gold border-zem-gold/40',
                'preparing': 'bg-blue-100 text-blue-700 border-blue-300',
                'served': 'bg-green-100 text-green-700 border-green-300',
                'paid': 'bg-emerald-100 text-emerald-700 border-emerald-300',
                'completed': 'bg-gray-100 text-gray-600 border-gray-300',
                'cancelled': 'bg-red-100 text-red-700 border-red-300',
            };
            const cls = colors[status] || 'bg-zem-bg text-zem-muted border-zem-border';
            return '<span class="rounded-full border px-3 py-1 text-xs font-bold ' + cls + '">' + status + '</span>';
        },
    }
}
</script>
@endsection
