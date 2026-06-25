<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected function restaurant(Request $request)
    {
        abort_unless($request->user()->restaurant_id, 403);
        return $request->user()->restaurant;
    }

    protected function visibleOrders(Request $request, $restaurant)
    {
        $query = $restaurant->orders();

        if ($request->session()->get('staff_profile_role') === 'kitchen') {
            $query->whereNotNull('confirmed_at');
        }

        return $query;
    }

    protected function serializeOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'table_number' => $order->table_number,
            'status' => $order->status,
            'total' => (float) $order->total,
            'note' => $order->note,
            'order_type' => $order->order_type ?? 'dine_in',
            'payment_method' => $order->payment_method,
            'confirmed' => (bool) $order->confirmed_at,
            'needs_confirmation' => $this->needsConfirmation($order),
            'created_at' => $order->created_at->toIso8601String(),
            'items' => $order->items->map(fn ($item) => [
                'quantity' => $item->quantity,
                'name' => $item->item_name,
                'note' => $item->note,
                'total_price' => (float) $item->total_price,
            ]),
        ];
    }

    protected function needsConfirmation(Order $order): bool
    {
        return (bool) $order->guest_session_id
            && ! $order->confirmed_at
            && ! in_array($order->status, ['completed', 'cancelled'], true);
    }

    public function index(Request $request)
    {
        $restaurant = $this->restaurant($request);
        $todayOrders = $restaurant->orders()->whereDate('created_at', today());

        $last7Days = now()->subDays(6)->startOfDay();
        $popularItems = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select('order_items.item_name')
            ->selectRaw("SUM(order_items.quantity) as quantity_sold, COALESCE(SUM(CASE WHEN orders.status IN ('paid', 'completed') THEN order_items.total_price ELSE 0 END), 0) as revenue_total")
            ->where('orders.restaurant_id', $restaurant->id)
            ->where('orders.created_at', '>=', $last7Days)
            ->whereNotIn('orders.status', ['cancelled'])
            ->groupBy('order_items.item_name')
            ->orderByDesc('quantity_sold')
            ->limit(5)
            ->get();

        return view('restaurant.dashboard', [
            'restaurant' => $restaurant,
            'todayOrders' => (clone $todayOrders)->count(),
            'newOrders' => (clone $todayOrders)->where('status', 'new')->count(),
            'preparingOrders' => (clone $todayOrders)->where('status', 'preparing')->count(),
            'servedOrders' => (clone $todayOrders)->whereIn('status', ['served', 'paid', 'completed'])->count(),
            'completedOrders' => (clone $todayOrders)->where('status', 'completed')->count(),
            'revenue' => (clone $todayOrders)->whereIn('status', ['paid', 'completed'])->sum('total'),
            'allOrders' => $restaurant->orders()->count(),
            'recentOrders' => $restaurant->orders()->with('items')->latest()->limit(8)->get(),
            'popularItems' => $popularItems,
            'latestOrderId' => $restaurant->orders()->max('id') ?? 0,
            'latestRequestId' => $restaurant->serviceRequests()->max('id') ?? 0,
        ]);
    }

    public function orders(Request $request)
    {
        $restaurant = $this->restaurant($request);
        $ordersQuery = $this->visibleOrders($request, $restaurant);

        return view('restaurant.orders.index', [
            'restaurant' => $restaurant,
            'orders' => (clone $ordersQuery)->with(['items'])->latest()->paginate(30),
            'menuItems' => $restaurant->menuItems()->where('is_available', true)->orderBy('name')->get(),
            'categories' => $restaurant->categories()
                ->with(['menuItems' => fn ($query) => $query->where('is_available', true)->orderBy('sort_order')->orderBy('name')])
                ->where('is_active', true)
                ->get(),
            'tables' => $restaurant->tables()->where('is_active', true)->orderBy('table_number')->get(),
            'requests' => $restaurant->serviceRequests()
                ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'acknowledged' THEN 2 WHEN 'completed' THEN 3 ELSE 4 END")
                ->latest()
                ->limit(40)
                ->get(),
            'activeRequests' => $restaurant->serviceRequests()->whereIn('status', ['pending', 'acknowledged'])->count(),
            'statuses' => Order::STATUSES,
            'paymentMethods' => Order::PAYMENT_METHODS,
            'latestOrderId' => (clone $ordersQuery)->max('id') ?? 0,
            'latestConfirmedAt' => (clone $ordersQuery)->max('confirmed_at'),
            'latestRequestId' => $restaurant->serviceRequests()->max('id') ?? 0,
            'todayOrdersCount' => $restaurant->orders()->whereDate('created_at', today())->count(),
            'todayRevenue' => $restaurant->orders()->whereDate('created_at', today())->whereIn('status', ['paid', 'completed'])->sum('total'),
        ]);
    }

    public function storeManualOrder(Request $request)
    {
        $restaurant = $this->restaurant($request);
        abort_unless(in_array($request->session()->get('staff_profile_role'), ['owner_manager', 'cashier'], true), 403);

        $data = $request->validate([
            'order_mode' => ['required', 'in:table,takeaway,delivery'],
            'table_number' => ['required_if:order_mode,table', 'nullable', 'string', 'max:50'],
            'delivery_app' => ['required_if:order_mode,delivery', 'nullable', 'string', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
        ]);

        $menuItemIds = collect($data['items'])->pluck('id')->unique();
        $menuItems = MenuItem::where('restaurant_id', $restaurant->id)
            ->whereIn('id', $menuItemIds)
            ->where('is_available', true)
            ->get()
            ->keyBy('id');

        if ($menuItems->count() !== $menuItemIds->count()) {
            return back()->withErrors(['items' => 'Some selected items are no longer available.'])->withInput();
        }

        $tableNumber = match ($data['order_mode']) {
            'takeaway' => 'Takeaway',
            'delivery' => 'Delivery',
            default => $data['table_number'],
        };

        $table = $data['order_mode'] === 'table'
            ? $restaurant->tables()
                ->where('table_number', $tableNumber)
                ->where('is_active', true)
                ->first()
            : null;

        $order = DB::transaction(function () use ($data, $restaurant, $table, $tableNumber, $menuItems) {
            $orderNote = $data['note'] ?? null;
            if ($data['order_mode'] === 'delivery') {
                $orderNote = trim('Delivery app: '.($data['delivery_app'] ?? '').($orderNote ? "\n".$orderNote : ''));
            }

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
                'table_id' => $table?->id,
                'table_number' => $tableNumber,
                'customer_name' => $data['order_mode'] === 'delivery' ? ($data['delivery_app'] ?? null) : ($data['customer_name'] ?? null),
                'customer_phone' => $data['customer_phone'] ?? null,
                'note' => $orderNote,
                'status' => 'new',
                'payment_status' => 'unpaid',
                'confirmed_at' => now(),
                'subtotal' => $subtotal,
                'service_charge' => $serviceCharge,
                'tax' => $tax,
                'total' => $total,
                'order_type' => $data['order_mode'] === 'delivery' ? 'delivery' : 'dine_in',
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

        $label = $restaurant->isHotel() ? 'Room service order' : 'Manual order';

        return back()->with('success', $label.' #'.$order->id.' created.');
    }

    public function poll(Request $request)
    {
        $restaurant = $this->restaurant($request);
        $ordersQuery = $this->visibleOrders($request, $restaurant);
        $orderSince = (int) $request->query('order_since', 0);
        $confirmedSince = $request->query('confirmed_since');
        $requestSince = (int) $request->query('request_since', 0);
        $visibleOrderIds = collect($request->query('visible_order_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->take(100);

        $role = $request->session()->get('staff_profile_role', 'owner_manager');
        $cacheKey = "poll:{$restaurant->id}:{$role}:{$orderSince}:{$confirmedSince}:{$requestSince}:" . $visibleOrderIds->implode('-');
        if ($cached = Cache::get($cacheKey)) {
            return response()->json($cached);
        }

        $latestOrderId = (int) ((clone $ordersQuery)->max('id') ?? 0);
        $latestConfirmedAt = (clone $ordersQuery)->max('confirmed_at');
        $latestRequestId = (int) ($restaurant->serviceRequests()->max('id') ?? 0);
        $hasNewOrderIds = $latestOrderId > $orderSince;
        $hasNewConfirmations = $role === 'kitchen' && $confirmedSince && $latestConfirmedAt && $latestConfirmedAt > $confirmedSince;
        $orderStatuses = $visibleOrderIds->isEmpty()
            ? collect()
            : (clone $ordersQuery)->whereIn('id', $visibleOrderIds)->get(['id', 'status', 'confirmed_at'])->values();

        if (! $hasNewOrderIds && ! $hasNewConfirmations && $latestRequestId <= $requestSince) {
            $response = [
                'orders' => [],
                'requests' => [],
                'orderStatuses' => $orderStatuses,
                'latestOrderId' => max($orderSince, $latestOrderId),
                'latestConfirmedAt' => $latestConfirmedAt,
                'latestRequestId' => max($requestSince, $latestRequestId),
                'activeRequests' => null,
                'hasChanges' => false,
            ];
            Cache::put($cacheKey, $response, 5);
            return response()->json($response);
        }

        $newOrdersRaw = ($hasNewOrderIds || $hasNewConfirmations)
            ? (clone $ordersQuery)
                ->with('items')
                ->where(function ($query) use ($orderSince, $confirmedSince, $role) {
                    $query->where('id', '>', $orderSince);
                    if ($role === 'kitchen' && $confirmedSince) {
                        $query->orWhere('confirmed_at', '>', $confirmedSince);
                    }
                })
                ->orderBy('id')
                ->limit(100)
                ->get()
            : collect();

        $newOrders = $newOrdersRaw->map(fn ($order) => $this->serializeOrder($order));

        $newRequestsRaw = $latestRequestId > $requestSince
            ? $restaurant->serviceRequests()
                ->where('id', '>', $requestSince)
                ->orderBy('id')
                ->limit(100)
                ->get()
            : collect();

        $newRequests = $newRequestsRaw->map(fn ($req) => [
                'id' => $req->id,
                'table_number' => $req->table_number,
                'type' => $req->type,
                'status' => $req->status,
                'note' => $req->note,
                'created_at' => $req->created_at->toIso8601String(),
            ]);

        $response = [
            'orders' => $newOrders,
            'requests' => $newRequests,
            'orderStatuses' => $orderStatuses,
            'latestOrderId' => max($orderSince, $latestOrderId, (int) ($newOrders->max('id') ?? 0)),
            'latestConfirmedAt' => $latestConfirmedAt,
            'latestRequestId' => max($requestSince, $latestRequestId, (int) ($newRequests->max('id') ?? 0)),
            'activeRequests' => $newRequestsRaw->isEmpty()
                ? null
                : $restaurant->serviceRequests()->whereIn('status', ['pending', 'acknowledged'])->count(),
            'hasChanges' => $newOrdersRaw->isNotEmpty() || $newRequestsRaw->isNotEmpty(),
        ];
        Cache::put($cacheKey, $response, 5);
        return response()->json($response);
    }

    public function analytics(Request $request)
    {
        $restaurant = $this->restaurant($request);
        $todayOrders = $restaurant->orders()->whereDate('created_at', today())->where('status', '!=', 'cancelled');
        $last30Days = now()->subDays(29)->startOfDay();
        $last30Orders = $restaurant->orders()->where('created_at', '>=', $last30Days)->where('status', '!=', 'cancelled');

        $dailyTrends = $restaurant->orders()
            ->selectRaw("DATE(created_at) as order_date, SUM(CASE WHEN status != 'cancelled' THEN 1 ELSE 0 END) as orders_count, COALESCE(SUM(CASE WHEN status IN ('paid', 'completed') THEN total ELSE 0 END), 0) as revenue_total")
            ->where('created_at', '>=', $last30Days)
            ->groupBy('order_date')
            ->orderByDesc('order_date')
            ->get();

        $topItems = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select('order_items.item_name')
            ->selectRaw("SUM(order_items.quantity) as quantity_sold, COALESCE(SUM(CASE WHEN orders.status IN ('paid', 'completed') THEN order_items.total_price ELSE 0 END), 0) as revenue_total")
            ->where('orders.restaurant_id', $restaurant->id)
            ->where('orders.created_at', '>=', $last30Days)
            ->whereNotIn('orders.status', ['cancelled'])
            ->groupBy('order_items.item_name')
            ->orderByDesc('quantity_sold')
            ->limit(8)
            ->get();

        $busiestTables = $restaurant->orders()
            ->select('table_number')
            ->selectRaw("COUNT(*) as orders_count, COALESCE(SUM(CASE WHEN status IN ('paid', 'completed') THEN total ELSE 0 END), 0) as revenue_total")
            ->where('created_at', '>=', $last30Days)
            ->where('status', '!=', 'cancelled')
            ->groupBy('table_number')
            ->orderByDesc('orders_count')
            ->limit(8)
            ->get();

        return view('restaurant.analytics', [
            'restaurant' => $restaurant,
            'todayOrders' => (clone $todayOrders)->count(),
            'todayRevenue' => (clone $todayOrders)->whereIn('status', ['paid', 'completed'])->sum('total'),
            'last30Orders' => (clone $last30Orders)->count(),
            'last30Revenue' => (clone $last30Orders)->whereIn('status', ['paid', 'completed'])->sum('total'),
            'completedOrders' => (clone $last30Orders)->where('status', 'completed')->count(),
            'topItems' => $topItems,
            'busiestTables' => $busiestTables,
            'dailyTrends' => $dailyTrends,
        ]);
    }

    public function updateOrder(Request $request, Order $order)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($order->restaurant_id === $restaurant->id, 403);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', Order::STATUSES)],
            'payment_method' => ['nullable', 'in:'.implode(',', Order::PAYMENT_METHODS)],
        ]);

        $profileId = $request->session()->get('staff_profile_id');
        $profileRole = $request->session()->get('staff_profile_role');

        abort_if($this->needsConfirmation($order) && $data['status'] !== 'cancelled', 422, 'Confirm this order before sending it forward.');
        abort_if($profileRole === 'cashier' && $data['status'] === 'paid' && $order->status !== 'served', 422, 'Kitchen must mark this order served before payment.');

        $updateData = [
            'status' => $data['status'],
            'payment_status' => in_array($data['status'], ['paid', 'completed'], true) ? 'paid' : $order->payment_status,
        ];

        // Record which cashier handled the payment
        if (in_array($data['status'], ['paid', 'completed'], true) && $profileRole === 'cashier' && $profileId) {
            $updateData['handled_by_profile_id'] = $profileId;
            if (! empty($data['payment_method'])) {
                $updateData['payment_method'] = $data['payment_method'];
            }
        }

        $order->update($updateData);

        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['success' => true, 'status' => $order->status]);
        }

        return back()->with('success', 'Order updated.');
    }

    public function confirmOrder(Request $request, Order $order)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($order->restaurant_id === $restaurant->id, 403);
        abort_unless(in_array($request->session()->get('staff_profile_role'), ['owner_manager', 'cashier'], true), 403);
        abort_if(in_array($order->status, ['completed', 'cancelled'], true), 422, 'This order can no longer be confirmed.');

        if (! $order->confirmed_at) {
            $order->update(['confirmed_at' => now()]);
        }

        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['success' => true, 'confirmed' => true]);
        }

        return back()->with('success', 'Order #'.$order->id.' confirmed.');
    }
}
