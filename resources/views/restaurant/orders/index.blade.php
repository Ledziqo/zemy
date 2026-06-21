@extends('layouts.dashboard', ['heading' => 'Work Board', 'eyebrow' => 'Live orders & service requests'])

@section('content')
@include('restaurant.partials.order_sound_alerts', ['latestOrderId' => $latestOrderId])
@php($placeTitle = $restaurant->locationLabelTitle())
@php($requestTypeLabels = ['call_waiter' => $restaurant->staffRequestLabel(), 'request_bill' => 'Request Bill'])
<div x-data="workBoard()" x-init="init()" x-cloak class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div class="grid gap-3 sm:grid-cols-4">
        <div class="rounded-md border border-zem-border bg-zem-card px-4 py-3"><p class="text-xs text-zem-muted">Active orders</p><p class="mt-1 text-2xl font-extrabold" x-text="activeCount">{{ $orders->whereNotIn('status', ['completed'])->count() }}</p></div>
        <div class="rounded-md border border-zem-border bg-zem-card px-4 py-3"><p class="text-xs text-zem-muted">Completed today</p><p class="mt-1 text-2xl font-extrabold" x-text="completedCount">{{ $orders->where('status', 'completed')->count() }}</p></div>
        <div class="rounded-md border border-zem-border bg-zem-card px-4 py-3"><p class="text-xs text-zem-muted">Active requests</p><p class="mt-1 text-2xl font-extrabold" x-text="activeRequests">{{ $activeRequests }}</p></div>
        <div class="rounded-md border border-zem-border bg-zem-card px-4 py-3"><p class="text-xs text-zem-muted">Updated</p><p class="mt-1 text-sm font-bold" x-text="updatedTime">{{ now()->format('H:i:s') }}</p></div>
    </div>
</div>

<div class="grid gap-5 xl:grid-cols-[minmax(0,1.5fr)_minmax(360px,.9fr)]">
    <section>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-display text-xl font-bold">Orders</h2>
            <div class="flex gap-2">
                <button @click="filter='active'" :class="filter==='active'?'bg-zem-gold text-white':'border border-zem-border'" class="rounded-full px-4 py-2 text-sm font-bold">Active</button>
                <button @click="filter='completed'" :class="filter==='completed'?'bg-zem-gold text-white':'border border-zem-border'" class="rounded-full px-4 py-2 text-sm font-bold">Completed</button>
                <button @click="filter='all'" :class="filter==='all'?'bg-zem-gold text-white':'border border-zem-border'" class="rounded-full px-4 py-2 text-sm font-bold">All</button>
            </div>
        </div>
        <div class="grid gap-4" x-ref="ordersList">
            @forelse($orders as $order)
                <article class="rounded-md border-l-4 border border-zem-border bg-zem-card p-4 {{ $order->status === 'completed' ? 'border-l-gray-400 opacity-60' : 'border-l-zem-gold' }}" data-order-id="{{ $order->id }}" data-status="{{ $order->status }}" x-show="filter==='all' || (filter==='active' && '{{ $order->status }}' !== 'completed') || (filter==='completed' && '{{ $order->status }}' === 'completed')">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><h2 class="font-display text-xl font-bold">Order #{{ $order->id }}</h2><p class="text-sm text-zem-muted">{{ $placeTitle }} {{ $order->table_number }} - {{ $order->created_at->diffForHumans() }}</p></div>
                        <x-status :status="$order->status" />
                    </div>
                    <div class="mt-4 space-y-2">
                        @foreach($order->items as $item)<p class="flex justify-between gap-3 rounded-md border border-zem-border bg-zem-bg px-3 py-2 text-sm text-zem-cream"><span>{{ $item->quantity }} x {{ $item->item_name }} @if($item->note)<em class="text-zem-muted">({{ $item->note }})</em>@endif</span><strong class="shrink-0 text-zem-cream">{{ number_format($item->total_price) }} ETB</strong></p>@endforeach
                    </div>
                    <p class="mt-3 text-sm text-zem-muted">Note: {{ $order->note ?: 'None' }}</p>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <strong>{{ number_format($order->total) }} ETB</strong>
                        @if($order->status !== 'completed')
                            <button type="button" @click="markCompleted({{ $order->id }})" class="rounded-md bg-zem-green px-4 py-2 text-sm font-bold text-white transition hover:opacity-90">Mark as completed</button>
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
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-display text-xl font-bold">Service requests</h2>
            </div>
            <div class="grid gap-3" x-ref="requestsList">
                @forelse($requests as $requestRow)
                    <div class="rounded-md border {{ in_array($requestRow->status, ['pending', 'acknowledged'], true) ? 'border-zem-gold/40 bg-zem-gold/10' : 'border-zem-border bg-zem-card opacity-60' }} p-4" data-request-id="{{ $requestRow->id }}" data-status="{{ $requestRow->status }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <strong>{{ $placeTitle }} {{ $requestRow->table_number }}</strong>
                                <p class="mt-1 text-sm text-zem-muted">{{ $restaurant->requestTypeLabel($requestRow->type) }} - {{ $requestRow->created_at->diffForHumans() }}</p>
                                @if($requestRow->note)<p class="mt-2 text-sm">{{ $requestRow->note }}</p>@endif
                            </div>
                            <x-status :status="$requestRow->status" />
                        </div>
                        @if($requestRow->status !== 'completed')
                            <button type="button" @click="markRequestCompleted({{ $requestRow->id }})" class="mt-3 w-full rounded-md bg-zem-green px-4 py-2 text-sm font-bold text-white transition hover:opacity-90">Mark as completed</button>
                        @endif
                    </div>
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
        pollUrl: '{{ route("restaurant.orders.poll") }}',
        orderUpdateUrl: '{{ route("restaurant.orders.update", ["__ID__"]) }}',
        requestUpdateUrl: '{{ route("restaurant.service-requests.update", ["__ID__"]) }}',
        requestTypeLabels: {{ json_encode($requestTypeLabels) }},
        placeTitle: '{{ $placeTitle }}',

        init() {
            this.latestOrderId = {{ $latestOrderId }};
            this.latestRequestId = {{ $requests->first()?->id ?? 0 }};
            this.activeRequests = {{ $activeRequests }};
            this.activeCount = {{ $orders->whereNotIn('status', ['completed'])->count() }};
            this.completedCount = {{ $orders->where('status', 'completed')->count() }};
            this.updatedTime = '{{ now()->format("H:i:s") }}';

            setInterval(() => this.poll(), 8000);
        },

        showToast(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;
            this.toast = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => this.toast = false, 3000);
        },

        markCompleted(orderId) {
            const url = this.orderUpdateUrl.replace('__ID__', orderId);
            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}');
            formData.append('_method', 'PATCH');
            formData.append('status', 'completed');

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const article = this.$refs.ordersList.querySelector('[data-order-id="' + orderId + '"]');
                    if (article) {
                        article.dataset.status = 'completed';
                        article.classList.remove('border-l-zem-gold');
                        article.classList.add('border-l-gray-400', 'opacity-60');
                        const btn = article.querySelector('button');
                        if (btn) btn.remove();
                        this.activeCount--;
                        this.completedCount++;
                    }
                    this.showToast('Order #' + orderId + ' completed');
                }
            })
            .catch(() => this.showToast('Failed to update order #' + orderId, 'error'));
        },

        markRequestCompleted(requestId) {
            const url = this.requestUpdateUrl.replace('__ID__', requestId);
            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}');
            formData.append('_method', 'PATCH');
            formData.append('status', 'completed');

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const el = this.$refs.requestsList.querySelector('[data-request-id="' + requestId + '"]');
                    if (el) {
                        el.dataset.status = 'completed';
                        el.classList.remove('border-zem-gold/40', 'bg-zem-gold/10');
                        el.classList.add('border-zem-border', 'bg-zem-card', 'opacity-60');
                        const btn = el.querySelector('button');
                        if (btn) btn.remove();
                    }
                    this.activeRequests--;
                    this.showToast('Request completed');
                }
            })
            .catch(() => this.showToast('Failed to update request', 'error'));
        },

        poll() {
            fetch(this.pollUrl + '?order_since=' + this.latestOrderId + '&request_since=' + this.latestRequestId, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(data => {
                this.updatedTime = new Date().toLocaleTimeString('en-GB');
                this.activeRequests = data.activeRequests;

                if (data.orders.length > 0) {
                    data.orders.forEach(order => {
                        if (order.id > this.latestOrderId) {
                            this.latestOrderId = order.id;
                            if (order.status !== 'completed') this.activeCount++;
                            else this.completedCount++;
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
            const isCompleted = order.status === 'completed';
            article.className = 'rounded-md border-l-4 border border-zem-border bg-zem-card p-4 animate-slide-in ' + (isCompleted ? 'border-l-gray-400 opacity-60' : 'border-l-zem-gold');
            article.dataset.orderId = order.id;
            article.dataset.status = order.status;
            article.setAttribute('x-show', "filter==='all' || (filter==='active' && '" + order.status + "' !== 'completed') || (filter==='completed' && '" + order.status + "' === 'completed')");

            let itemsHtml = order.items.map(item =>
                '<p class="flex justify-between gap-3 rounded-md border border-zem-border bg-zem-bg px-3 py-2 text-sm text-zem-cream"><span>' + item.quantity + ' x ' + item.name + (item.note ? '<em class="text-zem-muted">(' + item.note + ')</em>' : '') + '</span><strong class="shrink-0 text-zem-cream">' + new Intl.NumberFormat('en-US').format(item.total_price) + ' ETB</strong></p>'
            ).join('');

            const statusBadge = this.getStatusBadge(order.status);
            const completeBtn = isCompleted ? '' : '<button type="button" onclick="this.dispatchEvent(new CustomEvent(\'mark-completed\', {bubbles: true}))" class="rounded-md bg-zem-green px-4 py-2 text-sm font-bold text-white transition hover:opacity-90">Mark as completed</button>';

            article.innerHTML =
                '<div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-display text-xl font-bold">Order #' + order.id + '</h2><p class="text-sm text-zem-muted">' + this.placeTitle + ' ' + order.table_number + ' - ' + order.created_at + '</p></div>' + statusBadge + '</div>' +
                '<div class="mt-4 space-y-2">' + itemsHtml + '</div>' +
                '<p class="mt-3 text-sm text-zem-muted">Note: ' + (order.note || 'None') + '</p>' +
                '<div class="mt-4 flex flex-wrap items-center justify-between gap-3"><strong>' + new Intl.NumberFormat('en-US').format(order.total) + ' ETB</strong>' + completeBtn + '</div>';

            const btn = article.querySelector('button');
            if (btn) {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.markCompleted(order.id);
                });
            }

            list.prepend(article);
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
            const completeBtn = isCompleted ? '' : '<button type="button" class="mt-3 w-full rounded-md bg-zem-green px-4 py-2 text-sm font-bold text-white transition hover:opacity-90">Mark as completed</button>';

            div.innerHTML =
                '<div class="flex flex-wrap items-start justify-between gap-3"><div><strong>' + this.placeTitle + ' ' + req.table_number + '</strong><p class="mt-1 text-sm text-zem-muted">' + label + ' - ' + req.created_at + '</p>' + (req.note ? '<p class="mt-2 text-sm">' + req.note + '</p>' : '') + '</div>' + statusBadge + '</div>' +
                completeBtn;

            const btn = div.querySelector('button');
            if (btn) {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.markRequestCompleted(req.id);
                });
            }

            list.prepend(div);
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
            return '<span class="rounded-full border px-3 py-1 text-xs font-bold ' + cls + '">' + status + '</span>';
        },
    }
}
</script>
@endsection
