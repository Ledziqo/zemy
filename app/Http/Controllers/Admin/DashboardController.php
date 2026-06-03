<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Subscription;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $hasSubscriptions = Schema::hasTable('subscriptions');
        $hasDashboardAccessStatus = Schema::hasColumn('restaurants', 'dashboard_access_status');

        return view('admin.dashboard', [
            'totalRestaurants' => Restaurant::count(),
            'activeRestaurants' => Restaurant::where('is_active', true)->count(),
            'totalOrders' => Order::count(),
            'pendingDemoRequests' => DemoRequest::where('status', 'new')->count(),
            'activeSubscriptions' => $hasSubscriptions ? Subscription::where('status', 'active')->count() : 0,
            'unpaidSubscriptions' => $hasSubscriptions ? Subscription::where('status', 'unpaid')->count() : 0,
            'revokedRestaurants' => $hasDashboardAccessStatus ? Restaurant::where('dashboard_access_status', 'revoked')->count() : 0,
            'recentOrders' => Order::with('restaurant', 'items')->latest()->limit(10)->get(),
            'restaurants' => $hasSubscriptions ? Restaurant::with('subscriptions')->latest()->limit(12)->get() : Restaurant::latest()->limit(12)->get(),
            'hasDashboardAccessStatus' => $hasDashboardAccessStatus,
            'hasSubscriptions' => $hasSubscriptions,
        ]);
    }
}
