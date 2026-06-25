@extends('layouts.dashboard', ['heading' => 'Delivery Orders', 'eyebrow' => 'Manual delivery order entry'])

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-6 rounded-lg border border-zem-border bg-zem-card p-5">
        <h2 class="font-display text-lg font-bold">New Delivery Order</h2>
        <form method="post" action="{{ route('restaurant.delivery.store') }}" class="mt-4 space-y-4" x-data="{ items: [] }">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-1 text-sm">
                    <span class="font-bold">Customer Name</span>
                    <input name="customer_name" placeholder="Customer name" class="rounded-lg border border-zem-border bg-white px-3 py-2">
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-bold">Customer Phone</span>
                    <input name="customer_phone" placeholder="Phone number" class="rounded-lg border border-zem-border bg-white px-3 py-2">
                </label>
            </div>
            <div class="rounded-md border border-zem-border bg-zem-bg p-4">
                <p class="mb-3 text-sm font-bold">Add Items</p>
                <div class="flex gap-2">
                    <select x-model="selectedItem" class="flex-1 rounded-lg border border-zem-border bg-white px-3 py-2 text-sm">
                        <option value="">Select menu item...</option>
                        @foreach($menuItems as $item)
                            <option value="{{ $item->id }}" data-price="{{ $item->price }}">{{ $item->name }} - {{ number_format($item->price) }} ETB</option>
                        @endforeach
                    </select>
                    <input type="number" x-model="qty" placeholder="Qty" min="1" value="1" class="w-20 rounded-lg border border-zem-border bg-white px-3 py-2 text-sm">
                    <button type="button" @click="if (selectedItem) { items.push({id: selectedItem, name: $event.target.options[selectedIndex].text.split(' - ')[0], qty: qty}); selectedItem=''; qty=1 }" class="rounded-lg bg-zem-gold px-4 py-2 text-sm font-bold text-white">Add</button>
                </div>
                <div class="mt-3 space-y-2">
                    <template x-for="(item, i) in items" :key="i">
                        <div class="flex items-center justify-between rounded-md border border-zem-border bg-white px-3 py-2 text-sm">
                            <span x-text="item.name + ' x' + item.qty"></span>
                            <button type="button" @click="items.splice(i, 1)" class="text-red-500 font-bold">Remove</button>
                            <input type="hidden" :name="'items[' + i + '][id]'" :value="item.id">
                            <input type="hidden" :name="'items[' + i + '][quantity]'" :value="item.qty">
                        </div>
                    </template>
                </div>
            </div>
            <label class="grid gap-1 text-sm">
                <span class="font-bold">Note</span>
                <textarea name="note" rows="2" placeholder="Delivery note (optional)" class="rounded-lg border border-zem-border bg-white px-3 py-2"></textarea>
            </label>
            <button class="rounded-lg bg-zem-gold px-5 py-3 font-bold text-white">Create Delivery Order</button>
        </form>
    </div>

    <h2 class="mb-3 font-display text-lg font-bold">Recent Delivery Orders</h2>
    <div class="space-y-3">
        @forelse($deliveryOrders as $order)
            <div class="rounded-lg border border-zem-border bg-zem-card p-4">
                <div class="flex justify-between">
                    <div>
                        <p class="font-bold">Order #{{ $order->id }}</p>
                        <p class="text-sm text-zem-muted">{{ $order->customer_name ?? 'Unknown' }} - {{ $order->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold">{{ number_format($order->total) }} ETB</p>
                        <span class="text-xs rounded-full px-2 py-1 {{ $order->status === 'paid' || $order->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">{{ $order->status }}</span>
                    </div>
                </div>
                <div class="mt-2 space-y-1">
                    @foreach($order->items as $item)
                        <p class="text-sm text-zem-muted">{{ $item->quantity }}x {{ $item->item_name }} - {{ number_format($item->total_price) }} ETB</p>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-zem-border bg-zem-card p-8 text-center text-zem-muted">No delivery orders yet.</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $deliveryOrders->links() }}</div>
</div>
@endsection
