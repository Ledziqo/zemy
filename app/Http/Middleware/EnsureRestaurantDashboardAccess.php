<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureRestaurantDashboardAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $restaurant = $request->user()?->restaurant;
        if (! $restaurant) {
            return redirect()->route('login');
        }

        if (! Schema::hasColumn('restaurants', 'dashboard_access_status')) {
            return $next($request);
        }

        $latestSubscription = Schema::hasTable('subscriptions')
            ? $restaurant->subscriptions()->latest('starts_at')->first()
            : null;
        $status = $restaurant->dashboard_access_status ?? 'active';

        // Auto-lockout: if subscription end date has passed, set payment_required
        if ($latestSubscription && $latestSubscription->ends_at && $latestSubscription->ends_at < now()->toDateString()) {
            if ($status === 'active') {
                $restaurant->update(['dashboard_access_status' => 'payment_required']);
                $status = 'payment_required';
            }
        }

        if ($status !== 'active' || $latestSubscription?->status === 'unpaid') {
            return redirect()->route('restaurant.access-required');
        }

        return $next($request);
    }
}
