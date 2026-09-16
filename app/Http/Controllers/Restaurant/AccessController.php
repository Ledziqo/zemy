<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccessController extends Controller
{
    public function show(Request $request)
    {
        $restaurant = $request->user()->restaurant;
        $subscription = $restaurant->subscriptions()->latest('starts_at')->latest('id')->first();
        $accessStatus = $restaurant->dashboard_access_status ?? 'active';
        $paymentBlocked = $accessStatus === 'payment_required'
            || ($subscription && ($subscription->status === 'unpaid' || $subscription->isExpired()));

        return view('restaurant.access_required', [
            'restaurant' => $restaurant,
            'subscription' => $subscription,
            'accessReason' => $accessStatus === 'revoked'
                ? 'revoked'
                : ($restaurant->is_active === false ? 'inactive' : ($paymentBlocked ? 'payment' : 'support')),
        ]);
    }
}
