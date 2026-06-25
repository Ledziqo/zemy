<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffProfileSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('staff_profile_id')) {
            return $next($request);
        }

        return redirect()->route('restaurant.profile-select');
    }
}
