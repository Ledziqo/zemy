<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Support\GuestVisitManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function store(Request $request, GuestVisitManager $visits, string $restaurant_slug, string $table_number)
    {
        $restaurant = Restaurant::where('slug', $restaurant_slug)->where('is_active', true)->firstOrFail();
        $restaurantTable = $restaurant->tables()->where('table_number', $table_number)->where('is_active', true)->firstOrFail();
        $visit = $visits->resolve($request, $restaurant, $restaurantTable);

        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['nullable', Rule::in($restaurant->settings['payment_methods'] ?? ['cash', 'telebirr', 'cbe', 'awash', 'abyssinia'])],
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

        $order = DB::transaction(function () use ($data, $restaurant, $restaurantTable, $visit, $table_number, $menuItems) {
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
                'table_id' => $restaurantTable->id,
                'guest_session_id' => $visit->id,
                'table_number' => $table_number,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'note' => $data['note'] ?? null,
                'status' => 'new',
                'payment_method' => $data['payment_method'] ?? null,
                'payment_status' => 'unpaid',
                'subtotal' => $subtotal,
                'service_charge' => $serviceCharge,
                'tax' => $tax,
                'total' => $total,
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

        return redirect()->route('menu.confirmation', [$restaurant->slug, $table_number])
            ->with('order_id', $order->id)
            ->withCookie($visits->cookie($visit));
    }

    public function cancel(Request $request, GuestVisitManager $visits, string $restaurant_slug, string $table_number, Order $order)
    {
        $restaurant = Restaurant::where('slug', $restaurant_slug)->where('is_active', true)->firstOrFail();
        $restaurantTable = $restaurant->tables()->where('table_number', $table_number)->where('is_active', true)->firstOrFail();
        $visit = $visits->current($request, $restaurant, $restaurantTable);
        abort_unless($visit && $order->restaurant_id === $restaurant->id && $order->table_id === $restaurantTable->id && $order->guest_session_id === $visit->id, 404);

        $result = DB::transaction(function () use ($order, $visit) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->guest_session_id !== $visit->id) {
                abort(404);
            }
            if ($locked->status !== 'new') {
                return 'started';
            }
            if (now()->gte($locked->created_at->copy()->addMinutes(2))) {
                return 'expired';
            }

            $locked->update(['status' => 'cancelled']);
            return 'cancelled';
        });

        $redirect = redirect()->route('menu.show', [$restaurant->slug, $table_number]);

        return match ($result) {
            'cancelled' => $redirect->with('success', 'Order cancelled.'),
            'expired' => $redirect->withErrors(['order' => 'The two-minute cancellation window has ended.']),
            default => $redirect->withErrors(['order' => 'This order can no longer be cancelled because preparation has started.']),
        };
    }
}
