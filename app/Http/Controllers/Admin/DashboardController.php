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
        $hasDashboardAccessStatus = Schema::hasColumn('restaurants', 'dashboard_access_status');

        return view('admin.dashboard', [
            'totalRestaurants' => Restaurant::count(),
            'activeRestaurants' => Restaurant::where('is_active', true)->count(),
            'totalOrders' => Order::count(),
            'pendingDemoRequests' => DemoRequest::where('status', 'new')->count(),
            'activeSubscriptions' => Subscription::where('status', 'active')->count(),
            'unpaidSubscriptions' => Subscription::where('status', 'unpaid')->count(),
            'revokedRestaurants' => $hasDashboardAccessStatus ? Restaurant::where('dashboard_access_status', 'revoked')->count() : 0,
            'recentOrders' => Order::with('restaurant', 'items')->latest()->limit(10)->get(),
            'restaurants' => Restaurant::with('subscriptions')->latest()->limit(12)->get(),
            'hasDashboardAccessStatus' => $hasDashboardAccessStatus,
        ]);
    }
}
