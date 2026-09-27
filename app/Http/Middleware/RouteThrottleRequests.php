<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests;

class RouteThrottleRequests extends ThrottleRequests
{
    protected function resolveRequestSignature($request)
    {
        $route = $request->route();
        // Preserve the normal per-user/IP protection and route separation.
        // Guest requests also include their tenant so one venue cannot exhaust
        // another venue's order or service-request allowance from shared NAT.
        $tenant = $route->parameter('restaurant_slug');
        return hash('sha256', implode('|', [
            $route->getName() ?? $route->uri(),
            is_scalar($tenant) ? (string) $tenant : '',
            parent::resolveRequestSignature($request),
        ]));
    }
}
