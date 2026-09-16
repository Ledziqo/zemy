/* A browser-only product walkthrough. No accounts, API calls, payments or real orders. */
function zemtabDemo() {
    return {
        venue: 'restaurant', view: 'guest', category: 'all', cart: [], order: null,
        request: '', message: '', note: '',
        menu: [
            { id: 1, name: 'House pizza', description: 'Tomato, mozzarella and fresh basil.', price: 420, category: 'food', image: 'tulip-pizza.webp' },
            { id: 2, name: 'Banana cake', description: 'A soft slice, baked for a sweet break.', price: 160, category: 'food', image: 'tulip-banana-cake.webp' },
            { id: 3, name: 'Ethiopian coffee', description: 'Freshly brewed, served hot.', price: 80, category: 'drinks', image: 'tulip-traditional-coffee.webp' },
            { id: 4, name: 'Avocado juice', description: 'Smooth, refreshing and made to order.', price: 150, category: 'drinks', image: 'tulip-avocado-juice.webp' },
        ],
        get place() { return this.venue === 'hotel' ? 'Room 204' : 'Table 7'; },
        get visibleMenu() { return this.menu.filter(item => this.category === 'all' || item.category === this.category); },
        get count() { return this.cart.reduce((sum, item) => sum + item.quantity, 0); },
        get total() { return this.cart.reduce((sum, item) => sum + item.price * item.quantity, 0); },
        get nextAction() { return ({new: 'Confirm order', confirmed: 'Start preparing', preparing: 'Mark served', served: 'Record cash payment'})[this.order?.status] || ''; },
        get statusLabel() { return ({new: 'Awaiting confirmation', confirmed: 'Confirmed', preparing: 'Preparing', served: 'Served', paid: 'Paid · complete'})[this.order?.status] || ''; },
        money(value) { return new Intl.NumberFormat('en-US', {maximumFractionDigits: 2}).format(value) + ' ETB'; },
        quantity(id) { return this.cart.find(item => item.id === id)?.quantity || 0; },
        add(id) {
            const source = this.menu.find(item => item.id === id);
            if (!source) return;
            const row = this.cart.find(item => item.id === id);
            if (row) { if (row.quantity < 10) row.quantity++; }
            else this.cart.push({...source, quantity: 1});
            this.message = source.name + ' added to your demo order.';
        },
        remove(id) {
            const row = this.cart.find(item => item.id === id);
            if (!row) return;
            row.quantity--;
            this.cart = this.cart.filter(item => item.quantity > 0);
        },
        submit() {
            if (!this.cart.length || this.order) return;
            this.order = { id: 1042, place: this.place, items: this.cart.map(item => ({...item})), total: this.total, note: this.note.trim().slice(0, 160), status: 'new' };
            this.cart = []; this.note = ''; this.view = 'staff';
            this.message = 'Demo order sent. You are now seeing the staff view. Confirm the order to continue.';
        },
        advance() {
            if (!this.order) return;
            const next = {new: 'confirmed', confirmed: 'preparing', preparing: 'served', served: 'paid'}[this.order.status];
            if (!next) return;
            this.order.status = next;
            this.message = next === 'paid' ? 'Demo complete. No payment was taken.' : 'Demo order updated: ' + this.statusLabel + '.';
        },
        callStaff() { this.request = this.place + ' · ' + (this.venue === 'hotel' ? 'Guest requests assistance' : 'Guest called the waiter'); this.message = 'Demo request sent. Open Staff view to see it.'; },
        completeRequest() { this.request = ''; this.message = 'Demo service request completed.'; },
        reset(venue = this.venue) { this.venue = venue; this.view = 'guest'; this.category = 'all'; this.cart = []; this.order = null; this.request = ''; this.note = ''; this.message = 'Demo reset. Add an item to begin.'; },
    };
}
if (typeof module !== 'undefined' && module.exports) module.exports = zemtabDemo;
