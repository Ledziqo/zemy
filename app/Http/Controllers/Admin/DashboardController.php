<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Subscription;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'totalRestaurants' => Restaurant::count(),
            'activeRestaurants' => Restaurant::where('is_active', true)->count(),
            'totalOrders' => Order::count(),
            'pendingDemoRequests' => DemoRequest::where('status', 'new')->count(),
            'activeSubscriptions' => Subscription::where('status', 'active')->count(),
            'recentOrders' => Order::with('restaurant', 'items')->latest()->limit(10)->get(),
        ]);
    }
}
