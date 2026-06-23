<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EnsureRestaurantDashboardAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $restaurant = $request->user()?->restaurant;
        if (! $restaurant) {
            return redirect()->route('login');
        }

        // 5-minute file cache for subscription/access check — eliminates DB queries on every poll
        $cacheKey = "access:{$restaurant->id}";
        $allowed = Cache::remember($cacheKey, 300, function () use ($restaurant) {
            $status = $restaurant->dashboard_access_status ?? 'active';
            $latestSubscription = $restaurant->subscriptions()->latest('starts_at')->first();

            // Auto-lockout: if subscription end date has passed, set payment_required
            if ($latestSubscription && $latestSubscription->ends_at) {
                try {
                    if ($latestSubscription->ends_at->lessThan(today())) {
                        if ($status === 'active') {
                            $restaurant->update(['dashboard_access_status' => 'payment_required']);
                            $status = 'payment_required';
                        }
                    }
                } catch (\Exception $e) {}
            }

            return $status === 'active' && (! $latestSubscription || $latestSubscription->status !== 'unpaid');
        });

        if (! $allowed) {
            return redirect()->route('restaurant.access-required');
        }

        return $next($request);
    }
}
