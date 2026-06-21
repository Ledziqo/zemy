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
        $currentSubscriptions = $hasSubscriptions
            ? Subscription::query()
                ->whereIn('status', ['active', 'trial'])
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
            : null;
        $activeSubscribers = $currentSubscriptions
            ? (clone $currentSubscriptions)->where('status', 'active')
            : null;

        return view('admin.dashboard', [
            'totalRestaurants' => Restaurant::count(),
            'activeRestaurants' => Restaurant::where('is_active', true)->count(),
            'totalOrders' => Order::count(),
            'pendingDemoRequests' => DemoRequest::where('status', 'new')->count(),
            'activeSubscriptions' => $hasSubscriptions ? Subscription::where('status', 'active')->count() : 0,
            'unpaidSubscriptions' => $hasSubscriptions ? Subscription::where('status', 'unpaid')->count() : 0,
            'revokedRestaurants' => $hasDashboardAccessStatus ? Restaurant::where('dashboard_access_status', 'revoked')->count() : 0,
            'restaurants' => $hasSubscriptions
                ? Restaurant::with('subscriptions')->withCount('orders')->latest()->limit(12)->get()
                : Restaurant::withCount('orders')->latest()->limit(12)->get(),
            'hasDashboardAccessStatus' => $hasDashboardAccessStatus,
            'hasSubscriptions' => $hasSubscriptions,
            'monthlyRevenue' => $activeSubscribers ? (clone $activeSubscribers)->sum('monthly_price') : 0,
            'activeSubscriberCount' => $activeSubscribers ? (clone $activeSubscribers)->count() : 0,
            'totalRevenue' => $currentSubscriptions ? (clone $currentSubscriptions)->sum('monthly_price') : 0,
        ]);
    }

    public function database()
    {
        return view('admin.database');
    }
}
