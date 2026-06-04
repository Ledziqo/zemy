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
            'orders' => $restaurant->orders()->with('items')->latest()->paginate(30),
            'statuses' => Order::STATUSES,
            'latestOrderId' => $restaurant->orders()->max('id') ?? 0,
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

        return back()->with('success', 'Order updated.');
    }
}
