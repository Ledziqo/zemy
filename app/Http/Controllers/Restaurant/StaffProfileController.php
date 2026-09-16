<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\StaffProfile;
use Illuminate\Http\Request;

class StaffProfileController extends Controller
{
    protected function restaurant(Request $request)
    {
        abort_unless($request->user()->restaurant_id, 403);
        return $request->user()->restaurant;
    }

    protected function ensureOwnerManager(Request $request)
    {
        $role = $request->session()->get('staff_profile_role');
        abort_unless($role === 'owner_manager', 403, 'Only Owner/Manager can manage staff profiles.');
    }

    public function index(Request $request)
    {
        $this->ensureOwnerManager($request);
        $restaurant = $this->restaurant($request);
        $profiles = $restaurant->staffProfiles()->orderByRaw("CASE role WHEN 'owner_manager' THEN 1 WHEN 'cashier' THEN 2 WHEN 'kitchen' THEN 3 ELSE 4 END")->orderBy('name')->get();

        return view('restaurant.staff-profiles.index', [
            'restaurant' => $restaurant,
            'profiles' => $profiles,
        ]);
    }

    public function show(Request $request, StaffProfile $staffProfile)
    {
        $this->ensureOwnerManager($request);
        $restaurant = $this->restaurant($request);
        abort_unless($staffProfile->restaurant_id === $restaurant->id, 403);

        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->toDateString();
        $base = $restaurant->orders()
            ->where('handled_by_profile_id', $staffProfile->id)
            ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);

        $orders = (clone $base)->with(['items', 'table'])->latest('id')->paginate(30)->appends([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);

        return view('restaurant.staff-profiles.show', [
            'restaurant' => $restaurant,
            'profile' => $staffProfile,
            'orders' => $orders,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'orderCount' => (clone $base)->whereNotIn('status', ['cancelled'])->count(),
            'salesTotal' => (clone $base)->whereIn('status', ['paid', 'completed'])->sum('total'),
            'roomCreditTotal' => (clone $base)->where('payment_method', 'room_credit')->where('payment_status', '!=', 'paid')->sum('total'),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureOwnerManager($request);
        $restaurant = $this->restaurant($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:cashier,kitchen'],
            'password' => ['required', 'string', 'min:4', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        abort_if($data['role'] === 'kitchen' && ! $restaurant->kitchenScreenEnabled(), 422, 'Enable the Kitchen screen before adding a Kitchen profile.');

        StaffProfile::create([
            'restaurant_id' => $restaurant->id,
            'name' => $data['name'],
            'role' => $data['role'],
            'password' => $data['password'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Profile created successfully.');
    }

    public function update(Request $request, StaffProfile $staffProfile)
    {
        $this->ensureOwnerManager($request);
        $restaurant = $this->restaurant($request);
        abort_unless($staffProfile->restaurant_id === $restaurant->id, 403);

        abort_if($staffProfile->role === 'owner_manager', 403, 'Ask your administrator to update the Owner/Manager profile.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:cashier,kitchen'],
            'password' => ['nullable', 'string', 'min:4', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        abort_if($data['role'] === 'kitchen' && ! $restaurant->kitchenScreenEnabled(), 422, 'Enable the Kitchen screen before adding a Kitchen profile.');

        $updates = [
            'name' => $data['name'],
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($updates['role'] === 'kitchen' && ! $restaurant->kitchenScreenEnabled()) {
            $updates['is_active'] = false;
        }
        if (! empty($data['password'])) {
            $updates['password'] = $data['password'];
        }

        $staffProfile->update($updates);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function destroy(Request $request, StaffProfile $staffProfile)
    {
        $this->ensureOwnerManager($request);
        $restaurant = $this->restaurant($request);
        abort_unless($staffProfile->restaurant_id === $restaurant->id, 403);
        abort_unless($staffProfile->role !== 'owner_manager', 403, 'Cannot delete the Owner/Manager profile.');

        $staffProfile->delete();

        return back()->with('success', 'Profile deleted.');
    }
}
