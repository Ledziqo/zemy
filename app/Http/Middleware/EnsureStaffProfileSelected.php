<?php

namespace App\Http\Middleware;

use App\Models\StaffProfile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffProfileSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $restaurant = $request->user()?->restaurant;
        $profile = $restaurant && $request->session()->has('staff_profile_id')
            ? StaffProfile::where('restaurant_id', $restaurant->id)
                ->where('is_active', true)
                ->find($request->session()->get('staff_profile_id'))
            : null;

        if (! $profile || ! in_array($profile->role, StaffProfile::ROLES, true)
            || ($profile->role === 'kitchen' && ! $restaurant->kitchenScreenEnabled())) {
            $request->session()->forget(['staff_profile_id', 'staff_profile_role', 'staff_profile_name']);

            if ($request->expectsJson()) {
                abort(401, 'Select an active staff profile.');
            }

            return redirect()->route('restaurant.profile-select');
        }

        // Refresh legacy session consumers from the database, never from cached permissions.
        $request->session()->put('staff_profile_role', $profile->role);
        $request->session()->put('staff_profile_name', $profile->name);
        $request->attributes->set('staff_profile', $profile);

        $workboardRoutes = [
            'restaurant.dashboard', 'restaurant.orders.index', 'restaurant.orders.poll',
            'restaurant.orders.update',
        ];
        $cashierRoutes = [
            ...$workboardRoutes,
            'restaurant.orders.manual.store', 'restaurant.orders.confirm',
            'restaurant.service-requests.index', 'restaurant.service-requests.update',
        ];

        // Deny new/unnamed management routes by default for non-owner profiles.
        abort_unless($profile->role === 'owner_manager' || in_array($request->route()?->getName(),
            $profile->role === 'cashier' ? $cashierRoutes : $workboardRoutes, true), 403);

        return $next($request);
    }
}
