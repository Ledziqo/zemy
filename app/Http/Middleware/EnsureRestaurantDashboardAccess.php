<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRestaurantDashboardAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $restaurant = $request->user()?->restaurant;
        abort_unless($restaurant, 403);

        $latestSubscription = $restaurant->subscriptions()->latest()->first();
        $status = $restaurant->dashboard_access_status;

        if ($status !== 'active' || $latestSubscription?->status === 'unpaid') {
            return redirect()->route('restaurant.access-required');
        }

        return $next($request);
    }
}
