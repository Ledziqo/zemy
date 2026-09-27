<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests;

class RouteThrottleRequests extends ThrottleRequests
{
    protected function resolveRequestSignature($request)
    {
        $route = $request->route();
        // Preserve per-user/IP limits while isolating unrelated actions.
        return hash('sha256', ($route->getName() ?? $route->uri()).'|'.parent::resolveRequestSignature($request));
    }
}
