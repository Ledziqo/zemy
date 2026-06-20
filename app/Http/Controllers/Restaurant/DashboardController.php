<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

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
            ->selectRaw('SUM(order_items.quantity) as quantity_sold, COALESCE(SUM(order_items.total_price), 0) as revenue_total')
            ->where('orders.restaurant_id', $restaurant->id)
            ->where('orders.created_at', '>=', $last7Days)
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
            'latestOrderId' => $restaurant->orders()->max('id') ?? 0,
        ]);
    }

    public function orders(Request $request)
    {
        $restaurant = $this->restaurant($request);

        return view('restaurant.orders.index', [
            'restaurant' => $restaurant,
            'orders' => $restaurant->orders()->with(['items'])->latest()->paginate(30),
            'requests' => $restaurant->serviceRequests()
                ->orderByRaw("FIELD(status, 'pending', 'acknowledged', 'completed')")
                ->latest()
                ->limit(40)
                ->get(),
            'activeRequests' => $restaurant->serviceRequests()->whereIn('status', ['pending', 'acknowledged'])->count(),
            'statuses' => Order::STATUSES,
            'latestOrderId' => $restaurant->orders()->max('id') ?? 0,
            'todayOrdersCount' => $restaurant->orders()->whereDate('created_at', today())->count(),
            'todayRevenue' => $restaurant->orders()->whereDate('created_at', today())->whereIn('status', ['paid', 'completed'])->sum('total'),
        ]);
    }

    public function poll(Request $request)
    {
        $restaurant = $this->restaurant($request);
        $since = (int) $request->query('since', 0);

        $newOrders = $restaurant->orders()
            ->with('items')
            ->where('id', '>', $since)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'table_number' => $order->table_number,
                'status' => $order->status,
                'total' => (float) $order->total,
                'note' => $order->note,
                'created_at' => $order->created_at->diffForHumans(),
                'items' => $order->items->map(fn ($item) => [
                    'quantity' => $item->quantity,
                    'name' => $item->item_name,
                    'note' => $item->note,
                    'total_price' => (float) $item->total_price,
                ]),
            ]);

        $newRequests = $restaurant->serviceRequests()
            ->where('id', '>', $since)
            ->orderByRaw("FIELD(status, 'pending', 'acknowledged', 'completed')")
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($req) => [
                'id' => $req->id,
                'table_number' => $req->table_number,
                'type' => $req->type,
                'status' => $req->status,
                'note' => $req->note,
                'created_at' => $req->created_at->diffForHumans(),
            ]);

        return response()->json([
            'orders' => $newOrders,
            'requests' => $newRequests,
            'latestOrderId' => $restaurant->orders()->max('id') ?? 0,
            'latestRequestId' => $restaurant->serviceRequests()->max('id') ?? 0,
            'activeRequests' => $restaurant->serviceRequests()->whereIn('status', ['pending', 'acknowledged'])->count(),
        ]);
    }

    public function analytics(Request $request)
    {
        $restaurant = $this->restaurant($request);
        $todayOrders = $restaurant->orders()->whereDate('created_at', today());
        $last30Days = now()->subDays(29)->startOfDay();
        $last30Orders = $restaurant->orders()->where('created_at', '>=', $last30Days);

        $dailyTrends = $restaurant->orders()
            ->selectRaw('DATE(created_at) as order_date, COUNT(*) as orders_count, COALESCE(SUM(total), 0) as revenue_total')
            ->where('created_at', '>=', $last30Days)
            ->groupBy('order_date')
            ->orderByDesc('order_date')
            ->get();

        $topItems = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select('order_items.item_name')
            ->selectRaw('SUM(order_items.quantity) as quantity_sold, COALESCE(SUM(order_items.total_price), 0) as revenue_total')
            ->where('orders.restaurant_id', $restaurant->id)
            ->where('orders.created_at', '>=', $last30Days)
            ->groupBy('order_items.item_name')
            ->orderByDesc('quantity_sold')
            ->limit(8)
            ->get();

        $busiestTables = $restaurant->orders()
            ->select('table_number')
            ->selectRaw('COUNT(*) as orders_count, COALESCE(SUM(total), 0) as revenue_total')
            ->where('created_at', '>=', $last30Days)
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

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'status' => $order->status]);
        }

        return back()->with('success', 'Order updated.');
    }
}
