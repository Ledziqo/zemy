/* Guest interactions; no external services or guest analytics. */
function guestBrowser(menuItems = []) {
    return {
        menuSearch: '', activeCategory: 'all', selectedItem: null,
        normalized(value) { return String(value || '').normalize('NFKC').toLocaleLowerCase().trim(); },
        categoryVisible(id) { return this.activeCategory === 'all' || this.activeCategory === 'cat-' + id; },
        itemVisible(id, name, description) { return this.categoryVisible(id) && this.normalized(name + ' ' + description).includes(this.normalized(this.menuSearch)); },
        categoryHasVisibleItems(id) { return menuItems.some(i => i.category === id && this.itemVisible(id, i.name, i.description)); },
        hasVisibleItems() { return menuItems.some(i => this.itemVisible(i.category, i.name, i.description)); },
        resetSearch() { this.menuSearch = ''; this.activeCategory = 'all'; },
    };
}

function guestOrderProgress(url, initialStatus, labels) {
    return {
        status: initialStatus, message: '', timer: null, stopped: false, failures: 0,
        label() { return labels[this.status]?.[0] || this.status; },
        detail() { return labels[this.status]?.[1] || ''; },
        terminal() { return ['paid', 'completed', 'cancelled'].includes(this.status); },
        init() { this.schedule(); },
        destroy() { this.stopped = true; clearTimeout(this.timer); },
        schedule() { if (!this.stopped && !this.terminal()) this.timer = setTimeout(() => this.refresh(), 15000); },
        async refresh() {
            if (this.stopped || this.terminal()) return;
            if (document.hidden || !navigator.onLine) { this.message = 'Updates paused. Reconnect and keep this page open.'; this.schedule(); return; }
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, cache: 'no-store', signal: AbortSignal.timeout(10000) });
                if (!response.ok) { if ([401, 403, 404, 419].includes(response.status)) this.stopped = true; throw new Error(); }
                const data = await response.json();
                if (labels[data.status]) this.status = data.status;
                this.message = ''; this.failures = 0;
            } catch (_) { this.message = 'Live updates unavailable. Ask staff for the latest status.'; if (++this.failures >= 5) this.stopped = true; }
            this.schedule();
        },
    };
}

// Keep keyboard focus in the open sheet and return it to the trigger on closing.
const guestDialogTriggers = new WeakMap();
function guestDialogState(element, open) {
    if (open) {
        guestDialogTriggers.set(element, document.activeElement);
        setTimeout(() => element.querySelector('button, input, select, textarea, a[href]')?.focus(), 0);
    } else {
        guestDialogTriggers.get(element)?.focus(); guestDialogTriggers.delete(element);
    }
    setTimeout(() => { document.body.style.overflow = [...document.querySelectorAll('[aria-modal="true"]')].some(el => el.getClientRects().length) ? 'hidden' : ''; }, 0);
}
document.addEventListener('keydown', event => {
    if (event.key !== 'Tab') return;
    const dialog = [...document.querySelectorAll('[aria-modal="true"]')].find(el => el.getClientRects().length);
    if (!dialog) return;
    const controls = [...dialog.querySelectorAll('button, input, textarea, select, a[href], [tabindex="0"]')].filter(el => !el.disabled && el.getClientRects().length);
    const first = controls[0], last = controls.at(-1);
    if (event.shiftKey && (document.activeElement === first || !dialog.contains(document.activeElement))) { event.preventDefault(); last?.focus(); }
    else if (!event.shiftKey && (document.activeElement === last || !dialog.contains(document.activeElement))) { event.preventDefault(); first?.focus(); }
});

function menuCart(config) {
    return {
        ...guestBrowser(config.menuItems),
        open: false, submitting: false, requestBusy: false, requestMessage: '', requestUntil: {}, requestClock: Date.now(), requestTimer: null,
        activeCategory: 'all',
        menuSearch: '',
        items: [],
        clientRequestId: null,
        cartKey: config.cartKey,
        paymentDetails: config.paymentDetails || {},
        serviceChargePercentage: Number(config.serviceChargePercentage || 0),
        vatPercentage: Number(config.vatPercentage || 0),
        init() {
            try {
                const saved = JSON.parse(localStorage.getItem(this.cartKey) || 'null');
                if (Array.isArray(saved?.items)) this.items = saved.items.filter(item => Number.isInteger(item.id) && Number.isInteger(item.quantity) && item.quantity > 0 && item.quantity <= 50 && Number.isFinite(Number(item.price)) && Number(item.price) >= 0);
                if (typeof saved?.clientRequestId === 'string') this.clientRequestId = saved.clientRequestId;
            } catch (_) {}
            this.requestTimer = setInterval(() => { this.requestClock = Date.now(); }, 1000);
        },
        destroy() { clearInterval(this.requestTimer); },
        persistCart() {
            try { localStorage.setItem(this.cartKey, JSON.stringify({ items: this.items, clientRequestId: this.clientRequestId })); } catch (_) {}
        },
        add(item, quantity = 1) { const existing = this.items.find(i => i.id === item.id); existing ? existing.quantity = Math.min(50, existing.quantity + quantity) : this.items.push({...item, quantity: Math.min(50, quantity), note: ''}); this.clientRequestId = null; this.persistCart(); },
        dec(id) { const item = this.items.find(i => i.id === id); if (!item) return; item.quantity--; this.clientRequestId = null; if (item.quantity <= 0) this.items = this.items.filter(i => i.id !== id); this.persistCart(); },
        count() { return this.items.reduce((sum, item) => sum + item.quantity, 0); },
        total() { return this.items.reduce((sum, item) => sum + item.quantity * item.price, 0); },
        extraPercentage() { return this.serviceChargePercentage + this.vatPercentage; },
        extraCharges() { return this.total() * (this.extraPercentage() / 100); },
        finalTotal() { return this.total() + this.extraCharges(); },
        money(value) { return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(value) + ' ETB'; },
        requestCooling(type) { return (this.requestUntil[type] || 0) > this.requestClock; },
        async sendRequest(event) {
            const form = event.target, type = new FormData(form).get('type');
            if (this.requestBusy || this.requestCooling(type)) return;
            this.requestBusy = true; this.requestMessage = config.labels.sendingRequest;
            try {
                const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, signal: AbortSignal.timeout(15000) });
                if (!response.ok) throw new Error();
                const data = await response.json(); this.requestMessage = data.message;
                this.requestUntil[type] = Date.now() + data.cooldown * 1000;
            } catch (_) { this.requestMessage = config.labels.requestFailed; }
            finally { this.requestBusy = false; }
        },
        syncForm(event) {
            if (this.submitting || !this.items.length || !navigator.onLine) { event.preventDefault(); return; }
            this.submitting = true;
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
            if (!this.clientRequestId) {
                this.clientRequestId = window.crypto?.randomUUID?.() || ('zemtab-' + Date.now() + '-' + Math.random().toString(16).slice(2));
            }
            const requestId = document.createElement('input');
            requestId.type = 'hidden';
            requestId.name = 'client_request_id';
            requestId.value = this.clientRequestId;
            holder.appendChild(requestId);
            this.persistCart();
        }
    }
}

