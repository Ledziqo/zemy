<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    protected function restaurant(Request $request)
    {
        abort_unless($request->user()->restaurant_id, 403);
        return $request->user()->restaurant;
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

        return view('restaurant.orders.index', [
            'restaurant' => $restaurant,
            'orders' => $restaurant->orders()->with(['items'])->latest()->paginate(30),
            'requests' => $restaurant->serviceRequests()
                ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'acknowledged' THEN 2 WHEN 'completed' THEN 3 ELSE 4 END")
                ->latest()
                ->limit(40)
                ->get(),
            'activeRequests' => $restaurant->serviceRequests()->whereIn('status', ['pending', 'acknowledged'])->count(),
            'statuses' => Order::STATUSES,
            'latestOrderId' => $restaurant->orders()->max('id') ?? 0,
            'latestRequestId' => $restaurant->serviceRequests()->max('id') ?? 0,
            'todayOrdersCount' => $restaurant->orders()->whereDate('created_at', today())->count(),
            'todayRevenue' => $restaurant->orders()->whereDate('created_at', today())->whereIn('status', ['paid', 'completed'])->sum('total'),
        ]);
    }

    public function poll(Request $request)
    {
        $restaurant = $this->restaurant($request);
        $orderSince = (int) $request->query('order_since', 0);
        $requestSince = (int) $request->query('request_since', 0);
        $visibleOrderIds = collect($request->query('visible_order_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->take(100);

        // 5-second file cache: identical poll params return cached JSON without hitting the DB
        $cacheKey = "poll:{$restaurant->id}:{$orderSince}:{$requestSince}:" . $visibleOrderIds->implode('-');
        if ($cached = Cache::get($cacheKey)) {
            return response()->json($cached);
        }

        $latestOrderId = (int) ($restaurant->orders()->max('id') ?? 0);
        $latestRequestId = (int) ($restaurant->serviceRequests()->max('id') ?? 0);
        $orderStatuses = $visibleOrderIds->isEmpty()
            ? collect()
            : $restaurant->orders()->whereIn('id', $visibleOrderIds)->get(['id', 'status'])->values();

        if ($latestOrderId <= $orderSince && $latestRequestId <= $requestSince) {
            $response = [
                'orders' => [],
                'requests' => [],
                'orderStatuses' => $orderStatuses,
                'latestOrderId' => max($orderSince, $latestOrderId),
                'latestRequestId' => max($requestSince, $latestRequestId),
                'activeRequests' => null,
                'hasChanges' => false,
            ];
            Cache::put($cacheKey, $response, 5);
            return response()->json($response);
        }

        $newOrdersRaw = $latestOrderId > $orderSince
            ? $restaurant->orders()
                ->with('items')
                ->where('id', '>', $orderSince)
                ->orderBy('id')
                ->limit(100)
                ->get()
            : collect();

        $newOrders = $newOrdersRaw->map(fn ($order) => [
                'id' => $order->id,
                'table_number' => $order->table_number,
                'status' => $order->status,
                'total' => (float) $order->total,
                'note' => $order->note,
                'created_at' => $order->created_at->toIso8601String(),
                'items' => $order->items->map(fn ($item) => [
                    'quantity' => $item->quantity,
                    'name' => $item->item_name,
                    'note' => $item->note,
                    'total_price' => (float) $item->total_price,
                ]),
            ]);

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

        $data = $request->validate(['status' => ['required', 'in:'.implode(',', Order::STATUSES)]]);
        $order->update([
            'status' => $data['status'],
            'payment_status' => in_array($data['status'], ['paid', 'completed'], true) ? 'paid' : $order->payment_status,
        ]);

        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['success' => true, 'status' => $order->status]);
        }

        return back()->with('success', 'Order updated.');
    }
}
