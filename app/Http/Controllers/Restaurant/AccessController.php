<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccessController extends Controller
{
    public function show(Request $request)
    {
        $restaurant = $request->user()->restaurant;

        return view('restaurant.access_required', [
            'restaurant' => $restaurant,
            'subscription' => $restaurant->subscriptions()->latest()->first(),
        ]);
    }
}
