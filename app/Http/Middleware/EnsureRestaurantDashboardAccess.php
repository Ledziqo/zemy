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
        if (! $restaurant) {
            return redirect()->route('login');
        }

        // Access changes take effect on the next request, including an existing staff session.
        // Do not mutate billing state during reads or retain an obsolete permission in cache.
        $subscription = $restaurant->subscriptions()->latest('starts_at')->latest('id')->first();
        $allowed = $restaurant->is_active
            && ($restaurant->dashboard_access_status ?? 'active') === 'active'
            && (! $subscription || (
                in_array($subscription->status, ['active', 'trial'], true)
                && (! $subscription->ends_at || ! $subscription->ends_at->lessThan(today()))
            ));

        if (! $allowed) {
            if ($request->expectsJson()) {
                abort(403, 'Dashboard access is unavailable. Contact your manager or ZemTab support.');
            }
            return redirect()->route('restaurant.access-required');
        }

        return $next($request);
    }
}
