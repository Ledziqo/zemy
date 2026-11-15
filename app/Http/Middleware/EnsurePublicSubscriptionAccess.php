<?php

namespace App\Http\Middleware;

use App\Models\Restaurant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePublicSubscriptionAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('restaurant_slug');
        if (!$slug || !$request->is('r/*')) return $next($request);
        // Read current billing state so an existing QR or cached catalog cannot bypass a lock.
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        if ($restaurant->serviceAccessAllowed($restaurant->latestSubscription())) return $next($request);
        if ($request->expectsJson()) return response()->json(['message' => 'This service is temporarily unavailable. Please contact the venue.'], 403)->header('Cache-Control', 'no-store');
        return response()->view('menu.access_unavailable', ['restaurant' => $restaurant], 403)
            ->header('Cache-Control', 'private, no-store');
    }
}
