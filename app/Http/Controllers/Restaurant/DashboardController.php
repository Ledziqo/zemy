<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Support\GuestVisitManager;
use App\Support\ReportCache;
use App\Support\WorkBoardRevision;
use App\Support\WorkBoardSnapshotCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

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

    protected function orderFilter(Request $request): string
    {
        return in_array($request->query('filter'), ['active', 'completed', 'credit', 'all'], true)
            ? $request->query('filter') : 'active';
    }

    protected function boardOrders(Request $request, $restaurant)
    {
        $query = $this->visibleOrders($request, $restaurant)->with(['items', 'table']);
        $filter = $this->orderFilter($request);
        $kitchen = $request->session()->get('staff_profile_role') === 'kitchen';
        if ($filter === 'active') {
            // The live queue must never be hidden behind history pagination.
            return ($kitchen ? $query->whereIn('status', ['new', 'preparing']) : $query->whereNotIn('status', ['completed', 'cancelled']))->orderByDesc('id')->get();
        }
        if ($filter === 'completed') {
            $query->whereIn('status', $kitchen ? ['served', 'paid', 'completed'] : ['completed']);
        }
        if ($filter === 'credit') {
            if ($kitchen) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('status', 'completed')
                    ->where('payment_status', '!=', 'paid')
                    ->whereIn('payment_method', ['credit', 'room_credit']);
            }
        }
        return $query->orderByDesc('id')->paginate(30, ['*'], 'page', max(1, (int) $request->query('page', 1)))
            ->appends(['filter' => $filter]);
    }

    protected function boardCounts(Request $request, $restaurant): array
    {
        $query = $this->visibleOrders($request, $restaurant);
        $kitchen = $request->session()->get('staff_profile_role') === 'kitchen';
        $activeCondition = $kitchen
            ? "status IN ('new', 'preparing')"
            : "status NOT IN ('completed', 'cancelled')";
        $counts = (clone $query)->selectRaw(
            "SUM(CASE WHEN {$activeCondition} THEN 1 ELSE 0 END) AS active_count,
             SUM(CASE WHEN status = 'completed' AND DATE(created_at) = ? THEN 1 ELSE 0 END) AS completed_count,
             SUM(CASE WHEN status = 'completed' AND payment_status != 'paid' AND payment_method IN ('credit', 'room_credit') THEN 1 ELSE 0 END) AS due_credit_count,
             COALESCE(SUM(CASE WHEN status = 'completed' AND payment_status != 'paid' AND payment_method IN ('credit', 'room_credit') THEN total ELSE 0 END), 0) AS due_credit_total",
            [today()->toDateString()]
        )->first();

        return [
            'activeCount' => (int) ($counts->active_count ?? 0),
            'completedCount' => (int) ($counts->completed_count ?? 0),
            'dueCreditCount' => (int) ($counts->due_credit_count ?? 0),
            'dueCreditTotal' => (float) ($counts->due_credit_total ?? 0),
        ];
    }

    protected function serializeOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'table_number' => $order->table_number,
            'table_label' => $order->table?->displayLabel() ?: $order->table_number,
            'status' => $order->status,
            'total' => (float) $order->total,
            'note' => $order->note,
            'order_type' => $order->order_type ?? 'dine_in',
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
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
        if ($request->session()->get('staff_profile_role') !== 'owner_manager') {
            return $this->orders($request);
        }

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
            'recentOrders' => $restaurant->orders()->with(['items', 'table'])->latest('id')->limit(8)->get(),
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
            'pollUrl' => URL::temporarySignedRoute('restaurant.orders.poll', now()->addHours(16), [
                'restaurantId' => $restaurant->id,
                'session' => hash('sha256', $request->session()->getId()),
                'filter' => $this->orderFilter($request),
                'page' => max(1, (int) $request->query('page', 1)),
            ]),
            'orders' => $this->boardOrders($request, $restaurant),
            'filter' => $this->orderFilter($request),
            ...$this->boardCounts($request, $restaurant),
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
            'paymentMethods' => $restaurant->isHotel() ? Order::PAYMENT_METHODS : array_values(array_diff(Order::PAYMENT_METHODS, ['room_credit'])),
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
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.id' => ['required', 'integer', 'distinct', 'exists:menu_items,id'],
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

        if ($data['order_mode'] === 'table' && ! $table) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'table_number' => 'Select an active table or room belonging to this restaurant.',
            ]);
        }

        $order = DB::transaction(function () use ($data, $restaurant, $table, $tableNumber, $menuItems) {
            $orderNote = $data['note'] ?? null;
            if ($data['order_mode'] === 'delivery') {
                $orderNote = trim('Pickup source / driver: '.($data['delivery_app'] ?? '').($orderNote ? "\n".$orderNote : ''));
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

    public function poll(Request $request, int $restaurantId)
    {
        abort_unless(hash_equals(
            (string) $request->query('session', ''),
            hash('sha256', $request->session()->getId())
        ), 403);

        $revision = WorkBoardRevision::current($restaurantId);
        if ($request->header('X-Board-Revision') !== null && hash_equals($revision, (string) $request->header('X-Board-Revision'))) {
            return response('', 304)
                ->header('X-Board-Revision', (string) $revision)
                ->header('Cache-Control', 'private, no-store');
        }

        $restaurant = Restaurant::findOrFail($restaurantId);
        $role = (string) $request->session()->get('staff_profile_role', 'owner_manager');
        $filter = $this->orderFilter($request);
        $page = max(1, (int) $request->query('page', 1));
        $cacheKey = WorkBoardSnapshotCache::key($restaurantId, $revision, $role, $filter, $page);
        $payload = Cache::store('file')->remember($cacheKey, now()->addSeconds(25), fn () => $this->pollPayload($request, $restaurant, $revision));

        return response()->json($payload)->header('X-Board-Revision', (string) $revision)->header('Cache-Control', 'private, no-store');
    }

    private function pollPayload(Request $request, Restaurant $restaurant, string $revision): array
    {
        $ordersQuery = $this->visibleOrders($request, $restaurant);
        $orders = $this->boardOrders($request, $restaurant);
        $requests = $request->session()->get('staff_profile_role') === 'kitchen'
            ? collect()
            : $restaurant->serviceRequests()->with('table')
                ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'acknowledged' THEN 2 ELSE 3 END")
                ->orderByDesc('id')->limit(40)->get();

        $latestOrderId = (clone $ordersQuery)->max('id') ?? 0;
        $latestRequestId = $restaurant->serviceRequests()->max('id') ?? 0;

        return [
            'orders' => collect($orders instanceof \Illuminate\Pagination\LengthAwarePaginator ? $orders->items() : $orders->all())
                ->map(fn ($order) => $this->serializeOrder($order))->values()->all(),
            'requests' => $requests->map(fn ($row) => [
                'id' => $row->id,
                'table_number' => $row->table_number,
                'table_label' => $row->table?->displayLabel() ?: $row->table_number,
                'type' => $row->type,
                'status' => $row->status,
                'note' => $row->note,
                'created_at' => $row->created_at->toIso8601String(),
            ])->values()->all(),
            'pagination' => $this->orderFilter($request) === 'active'
                ? '' : $orders->withPath(route('restaurant.orders.index'))->links()->toHtml(),
            'latestOrderId' => $latestOrderId,
            'latestConfirmedAt' => (clone $ordersQuery)->max('confirmed_at'),
            'latestRequestId' => $latestRequestId,
            'revision' => $revision,
            'activeRequests' => $restaurant->serviceRequests()->whereIn('status', ['pending', 'acknowledged'])->count(),
            ...$this->boardCounts($request, $restaurant),
        ];
    }

    public function analytics(Request $request)
    {
        $restaurant = $this->restaurant($request);
        $revision = WorkBoardRevision::current((int) $restaurant->id);
        $payload = ReportCache::remember('analytics', (int) $restaurant->id, $revision, [], function () use ($restaurant): array {
            $todayOrders = $restaurant->orders()->whereDate('created_at', today())->where('status', '!=', 'cancelled');
            $last30Days = now()->subDays(29)->startOfDay();
            $last30Orders = $restaurant->orders()->where('created_at', '>=', $last30Days)->where('status', '!=', 'cancelled');

            $dailyTrends = $restaurant->orders()
                ->selectRaw("DATE(created_at) as order_date, SUM(CASE WHEN status != 'cancelled' THEN 1 ELSE 0 END) as orders_count, COALESCE(SUM(CASE WHEN status IN ('paid', 'completed') THEN total ELSE 0 END), 0) as revenue_total")
                ->where('created_at', '>=', $last30Days)
                ->groupBy('order_date')
                ->orderByDesc('order_date')
                ->get()
                ->map(fn ($row) => [
                    'order_date' => (string) $row->order_date,
                    'orders_count' => (int) $row->orders_count,
                    'revenue_total' => (float) $row->revenue_total,
                ])->all();

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
                ->get()
                ->map(fn ($row) => [
                    'item_name' => (string) $row->item_name,
                    'quantity_sold' => (int) $row->quantity_sold,
                    'revenue_total' => (float) $row->revenue_total,
                ])->all();

            $busiestTables = $restaurant->orders()
                ->select('table_number')
                ->selectRaw("COUNT(*) as orders_count, COALESCE(SUM(CASE WHEN status IN ('paid', 'completed') THEN total ELSE 0 END), 0) as revenue_total")
                ->where('created_at', '>=', $last30Days)
                ->where('status', '!=', 'cancelled')
                ->groupBy('table_number')
                ->orderByDesc('orders_count')
                ->limit(8)
                ->get()
                ->map(fn ($row) => [
                    'table_number' => (string) $row->table_number,
                    'orders_count' => (int) $row->orders_count,
                    'revenue_total' => (float) $row->revenue_total,
                ])->all();

            return [
                'todayOrders' => (clone $todayOrders)->count(),
                'todayRevenue' => (float) (clone $todayOrders)->whereIn('status', ['paid', 'completed'])->sum('total'),
                'last30Orders' => (clone $last30Orders)->count(),
                'last30Revenue' => (float) (clone $last30Orders)->whereIn('status', ['paid', 'completed'])->sum('total'),
                'completedOrders' => (clone $last30Orders)->where('status', 'completed')->count(),
                'topItems' => $topItems,
                'busiestTables' => $busiestTables,
                'dailyTrends' => $dailyTrends,
            ];
        });

        return view('restaurant.analytics', [
            'restaurant' => $restaurant,
            'todayOrders' => $payload['todayOrders'],
            'todayRevenue' => $payload['todayRevenue'],
            'last30Orders' => $payload['last30Orders'],
            'last30Revenue' => $payload['last30Revenue'],
            'completedOrders' => $payload['completedOrders'],
            'topItems' => collect($payload['topItems'])->map(fn ($row) => (object) $row),
            'busiestTables' => collect($payload['busiestTables'])->map(fn ($row) => (object) $row),
            'dailyTrends' => collect($payload['dailyTrends'])->map(fn ($row) => (object) $row),
        ]);
    }

    public function updateOrder(Request $request, Order $order, GuestVisitManager $visits)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($order->restaurant_id === $restaurant->id, 403);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', Order::STATUSES)],
            'payment_method' => ['nullable', 'in:'.implode(',', Order::PAYMENT_METHODS)],
        ]);

        $profileId = $request->session()->get('staff_profile_id');
        $profileRole = $request->session()->get('staff_profile_role');

        abort_unless(in_array($profileRole, ['owner_manager', 'cashier', 'kitchen'], true), 403);

        DB::transaction(function () use ($order, $restaurant, $data, $profileRole, $profileId) {
            // Serialize concurrent actions and validate against the current status.
            $current = $restaurant->orders()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_if($this->needsConfirmation($current) && $data['status'] !== 'cancelled', 422, 'Confirm this order before sending it forward.');
            abort_if(in_array($current->status, ['completed', 'cancelled'], true), 422, 'This order is already closed.');

            if ($data['status'] === 'cancelled') {
                abort_unless(in_array($profileRole, ['owner_manager', 'cashier'], true), 403);
                abort_if($current->status === 'paid' || $current->payment_status === 'paid', 422, 'Paid orders cannot be cancelled here. Process a refund first.');
            }

            if ($profileRole === 'kitchen') {
                abort_unless($restaurant->kitchenScreenEnabled() && $current->confirmed_at, 403);
                $allowed = ['new' => ['preparing'], 'preparing' => ['served']];
                abort_unless(in_array($data['status'], $allowed[$current->status] ?? [], true), 422, 'Kitchen can only start preparation or mark a preparing order served.');
            } elseif ($profileRole === 'cashier') {
                $allowed = $data['status'] === 'cancelled'
                    ? ['new' => ['cancelled'], 'preparing' => ['cancelled'], 'served' => ['cancelled']]
                    : ($restaurant->kitchenScreenEnabled()
                        ? ['served' => ['paid', 'completed'], 'paid' => ['completed']]
                        : ['new' => ['completed'], 'preparing' => ['completed'], 'served' => ['completed'], 'paid' => ['completed']]);
                abort_unless(in_array($data['status'], $allowed[$current->status] ?? [], true), 422, 'This transition is not available to this staff profile.');
            }

            if ($data['status'] === 'paid') {
                abort_if(empty($data['payment_method']), 422, 'Select a payment method.');
            }
            $paymentMethod = $data['payment_method'] ?? $current->payment_method;
            $isDeferredCredit = in_array($paymentMethod, ['credit', 'room_credit'], true);
            $isRoomCredit = $paymentMethod === 'room_credit';
            $settling = in_array($data['status'], ['paid', 'completed'], true);
            abort_if($data['status'] === 'completed' && empty($paymentMethod), 422, 'Select a payment method before completing this order.');
            abort_if($settling && $isDeferredCredit && $data['status'] !== 'completed', 422, 'Credit orders must be completed and settled later.');
            abort_if($settling && $isRoomCredit && ! $restaurant->isHotel(), 422, 'Room credit is available for hotel accounts only.');
            abort_if($settling && $isRoomCredit && $data['status'] !== 'completed', 422, 'Room credit orders must be completed and settled later.');
            $updateData = [
                'status' => $data['status'],
                'payment_status' => $isDeferredCredit && $data['status'] === 'completed' ? 'unpaid' : (in_array($data['status'], ['paid', 'completed'], true) ? 'paid' : $current->payment_status),
            ];
            if (in_array($data['status'], ['paid', 'completed'], true)) {
                if (in_array($profileRole, ['owner_manager', 'cashier'], true) && $profileId) {
                    $updateData['handled_by_profile_id'] = $profileId;
                }
                if (! empty($paymentMethod)) {
                    $updateData['payment_method'] = $paymentMethod;
                }
            }
            $current->update($updateData);
            $order->setRawAttributes($current->getAttributes(), true);
        });
        $visits->closeIfSettled($order->guest_session_id);

        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['success' => true, 'status' => $order->status]);
        }

        return back()->with('success', 'Order updated.');
    }

    public function markCreditPaid(Request $request, Order $order, GuestVisitManager $visits)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($order->restaurant_id === $restaurant->id, 403);
        abort_unless(in_array($request->session()->get('staff_profile_role'), ['owner_manager', 'cashier'], true), 403);

        $current = DB::transaction(function () use ($restaurant, $order) {
            $current = $restaurant->orders()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $eligibleCredit = $current->payment_method === 'credit' || ($current->payment_method === 'room_credit' && $restaurant->isHotel());
            abort_unless($eligibleCredit && $current->status === 'completed' && $current->payment_status !== 'paid', 422, 'This order is not an unpaid credit order.');
            $current->update(['payment_status' => 'paid']);
            return $current;
        });
        $visits->closeIfSettled($current->guest_session_id);

        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['success' => true, 'payment_status' => 'paid']);
        }

        return back()->with('success', 'Credit for order #'.$order->id.' marked paid.');
    }

    public function confirmOrder(Request $request, Order $order)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($order->restaurant_id === $restaurant->id, 403);
        abort_unless(in_array($request->session()->get('staff_profile_role'), ['owner_manager', 'cashier'], true), 403);
        DB::transaction(function () use ($restaurant, $order) {
            $current = $restaurant->orders()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_if(in_array($current->status, ['completed', 'cancelled'], true), 422, 'This order can no longer be confirmed.');
            if (! $current->confirmed_at) {
                $current->update(['confirmed_at' => now()]);
            }
        });

        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['success' => true, 'confirmed' => true]);
        }

        return back()->with('success', 'Order #'.$order->id.' confirmed.');
    }
}
