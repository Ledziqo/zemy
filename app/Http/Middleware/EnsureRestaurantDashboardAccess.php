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
        abort_unless($restaurant, 403);

        if (! Schema::hasColumn('restaurants', 'dashboard_access_status')) {
            return $next($request);
        }

        $latestSubscription = Schema::hasTable('subscriptions')
            ? $restaurant->subscriptions()->latest()->first()
            : null;
        $status = $restaurant->dashboard_access_status ?? 'active';

        if ($status !== 'active' || $latestSubscription?->status === 'unpaid') {
            return redirect()->route('restaurant.access-required');
        }

        return $next($request);
    }
}
