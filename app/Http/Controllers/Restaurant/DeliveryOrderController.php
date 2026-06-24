<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryOrderController extends Controller
{
    protected function restaurant(Request $request)
    {
        abort_unless($request->user()->restaurant_id, 403);
        return $request->user()->restaurant;
    }

    protected function ensureOwnerManager(Request $request)
    {
        $role = $request->session()->get('staff_profile_role');
        abort_unless($role === 'owner_manager', 403, 'Only Owner/Manager can create delivery orders.');
    }

    public function index(Request $request)
    {
        $this->ensureOwnerManager($request);
        $restaurant = $this->restaurant($request);

        $deliveryOrders = $restaurant->orders()
            ->where('order_type', 'delivery')
            ->with('items')
            ->latest()
            ->paginate(30);

        $menuItems = $restaurant->menuItems()->where('is_available', true)->orderBy('name')->get();

        return view('restaurant.delivery.index', [
            'restaurant' => $restaurant,
            'deliveryOrders' => $deliveryOrders,
            'menuItems' => $menuItems,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureOwnerManager($request);
        $restaurant = $this->restaurant($request);

        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
        ]);

        $menuItems = MenuItem::where('restaurant_id', $restaurant->id)
            ->whereIn('id', collect($data['items'])->pluck('id'))
            ->where('is_available', true)
            ->get()
            ->keyBy('id');

        if ($menuItems->count() !== count($data['items'])) {
            return back()->withErrors(['items' => 'Some selected items are no longer available.']);
        }

        $order = DB::transaction(function () use ($data, $restaurant, $menuItems) {
            $subtotal = 0;
            foreach ($data['items'] as $line) {
                $item = $menuItems[$line['id']];
                $subtotal += (float) $item->price * (int) $line['quantity'];
            }

            $settings = $restaurant->settings ?? [];
            $serviceCharge = $subtotal * ((float) ($settings['service_charge_percentage'] ?? 0) / 100);
            $tax = $subtotal * ((float) ($settings['vat_percentage'] ?? 0) / 100);
            $total = $subtotal + $serviceCharge + $tax;

            $order = Order::create([
                'restaurant_id' => $restaurant->id,
                'table_id' => null,
                'table_number' => 'Delivery',
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'note' => $data['note'] ?? null,
                'status' => 'new',
                'payment_status' => 'unpaid',
                'subtotal' => $subtotal,
                'service_charge' => $serviceCharge,
                'tax' => $tax,
                'total' => $total,
                'order_type' => 'delivery',
            ]);

            foreach ($data['items'] as $line) {
                $item = $menuItems[$line['id']];
                $order->items()->create([
                    'menu_item_id' => $item->id,
                    'item_name' => $item->name,
                    'quantity' => $line['quantity'],
                    'unit_price' => $item->price,
                    'total_price' => (float) $item->price * (int) $line['quantity'],
                    'note' => $line['note'] ?? null,
                ]);
            }

            return $order;
        });

        return back()->with('success', 'Delivery order created.');
    }
}
