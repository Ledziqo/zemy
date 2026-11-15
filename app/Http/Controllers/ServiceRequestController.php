<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Support\GuestVisitManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ServiceRequestController extends Controller
{
    public function store(Request $request, GuestVisitManager $visits, string $restaurant_slug, string $table_number)
    {
        $restaurant = Restaurant::where('slug', $restaurant_slug)->where('is_active', true)->firstOrFail();
        abort_if($restaurant->isMenuOnly(), 403, 'Service requests are disabled for this digital menu.');
        $restaurantTable = $restaurant->tables()->where('table_number', $table_number)->where('is_active', true)->firstOrFail();

        $data = $request->validate([
            'type' => ['required', Rule::in(['call_waiter', 'request_bill', 'request_water', 'other'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $visit = $visits->resolve($request, $restaurant, $restaurantTable);

        $created = DB::transaction(function () use ($restaurant, $restaurantTable, $visit, $table_number, $data) {
            // Serialize taps from the same table, including simultaneous first requests.
            $restaurantTable->newQuery()->whereKey($restaurantTable->id)->lockForUpdate()->firstOrFail();
            $duplicate = $restaurant->serviceRequests()->where('table_id', $restaurantTable->id)
                ->where('type', $data['type'])->where('status', 'pending')
                ->where('created_at', '>=', now()->subSeconds(60))->exists();
            if ($duplicate) return false;
            $restaurant->serviceRequests()->create([
                'table_id' => $restaurantTable->id,
                'guest_session_id' => $visit->id,
                'table_number' => $table_number,
                'type' => $data['type'],
                'note' => $data['note'] ?? null,
                'status' => 'pending',
            ]);
            return true;
        });

        $message = $created ? __('Request sent. A staff member will be with you shortly.') : __('Your request has already been sent. Staff will be with you shortly.');
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'cooldown' => 60])->withCookie($visits->cookie($visit));
        }

        return back()
            ->with('success', $message)
            ->withCookie($visits->cookie($visit));
    }
}
