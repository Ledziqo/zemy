<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index()
    {
        return view('admin.subscriptions.index', [
            'subscriptions' => Subscription::with('restaurant')->latest()->paginate(50),
            'restaurants' => Restaurant::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Subscription::create($this->validated($request));
        return back()->with('success', 'Subscription created.');
    }

    public function update(Request $request, Subscription $subscription)
    {
        $data = $this->validated($request);
        $subscription->update($data);

        $restaurant = $subscription->restaurant;
        if ($data['status'] === 'active') {
            $restaurant->update(['dashboard_access_status' => 'active']);
        } elseif ($data['status'] === 'unpaid' && $restaurant->dashboard_access_status !== 'revoked') {
            $restaurant->update(['dashboard_access_status' => 'payment_required']);
        }

        return back()->with('success', 'Subscription updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'restaurant_id' => ['required', 'exists:restaurants,id'],
            'plan_name' => ['required', 'string', 'max:255'],
            'monthly_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,unpaid,trial,cancelled'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ]);
    }
}
