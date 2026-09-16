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

        $billingRows = $hasSubscriptions
            ? Restaurant::with(['subscriptions' => function ($query) {
                $query->latest('starts_at')->latest('id');
            }])->orderBy('name')->get()->map(function ($restaurant) {
                $subscription = $restaurant->latestSubscription();
                $daysRemaining = $subscription?->daysRemaining();

                if (! $subscription) {
                    $attention = 'missing';
                    $label = 'No subscription';
                } elseif ($daysRemaining !== null && $daysRemaining < 0) {
                    $attention = 'overdue';
                    $label = 'Expired '.abs($daysRemaining).' days ago';
                } elseif ($subscription->status === 'unpaid') {
                    $attention = 'overdue';
                    $label = 'Payment required';
                } elseif ($daysRemaining !== null && $daysRemaining <= 7) {
                    $attention = 'due_soon';
                    $label = $daysRemaining === 0 ? 'Due today' : $daysRemaining.' days left';
                } elseif ($daysRemaining !== null && $daysRemaining <= 30) {
                    $attention = 'upcoming';
                    $label = $daysRemaining.' days left';
                } else {
                    $attention = 'current';
                    $label = $daysRemaining === null ? 'No expiry set' : $daysRemaining.' days left';
                }

                return (object) [
                    'restaurant' => $restaurant,
                    'subscription' => $subscription,
                    'daysRemaining' => $daysRemaining,
                    'attention' => $attention,
                    'label' => $label,
                ];
            })->sortBy(function ($row) {
                return ['overdue' => 0, 'missing' => 1, 'due_soon' => 2, 'upcoming' => 3, 'current' => 4][$row->attention] ?? 5;
            })->values()
            : collect();

        $billingAlerts = $billingRows->whereIn('attention', ['overdue', 'missing', 'due_soon'])->take(8);

        return view('admin.dashboard', [
            'totalRestaurants' => Restaurant::count(),
            'activeRestaurants' => Restaurant::where('is_active', true)->count(),
            'totalOrders' => Order::count(),
            'pendingDemoRequests' => DemoRequest::where('status', 'new')->count(),
            'activeSubscriptions' => $activeSubscribers ? (clone $activeSubscribers)->count() : 0,
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
            'billingRows' => $billingRows,
            'billingAlerts' => $billingAlerts,
            'billingDueSoon' => $billingRows->whereIn('attention', ['due_soon', 'upcoming'])->count(),
            'billingOverdue' => $billingRows->where('attention', 'overdue')->count(),
            'billingMissing' => $billingRows->where('attention', 'missing')->count(),
        ]);
    }

    public function database()
    {
        return view('admin.database');
    }
}
