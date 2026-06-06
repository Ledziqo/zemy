<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Support\GuestVisitManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceRequestController extends Controller
{
    public function store(Request $request, GuestVisitManager $visits, string $restaurant_slug, string $table_number)
    {
        $restaurant = Restaurant::where('slug', $restaurant_slug)->where('is_active', true)->firstOrFail();
        $restaurantTable = $restaurant->tables()->where('table_number', $table_number)->where('is_active', true)->firstOrFail();
        $visit = $visits->resolve($request, $restaurant, $restaurantTable);

        $data = $request->validate([
            'type' => ['required', Rule::in(['call_waiter', 'request_bill', 'request_water', 'other'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $restaurant->serviceRequests()->create([
            'table_id' => $restaurantTable->id,
            'guest_session_id' => $visit->id,
            'table_number' => $table_number,
            'type' => $data['type'],
            'note' => $data['note'] ?? null,
            'status' => 'pending',
        ]);

        return back()
            ->with('success', 'Request sent. A staff member will be with you shortly.')
            ->withCookie($visits->cookie($visit));
    }
}
