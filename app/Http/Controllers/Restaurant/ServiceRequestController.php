<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    private function restaurant(Request $request) { return $request->user()->restaurant; }

    public function index(Request $request)
    {
        $restaurant = $this->restaurant($request);
        return view('restaurant.service_requests.index', [
            'restaurant' => $restaurant,
            'requests' => $restaurant->serviceRequests()
                ->orderByRaw("FIELD(status, 'pending', 'acknowledged', 'completed')")
                ->latest()
                ->paginate(75),
            'activeRequests' => $restaurant->serviceRequests()->whereIn('status', ['pending', 'acknowledged'])->count(),
        ]);
    }

    public function update(Request $request, ServiceRequest $serviceRequest)
    {
        abort_unless($serviceRequest->restaurant_id === $this->restaurant($request)->id, 403);
        $serviceRequest->update($request->validate(['status' => ['required', 'in:pending,acknowledged,completed']]));
        return back()->with('success', 'Service request updated.');
    }
}
