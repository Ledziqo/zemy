<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\StaffProfile;
use Illuminate\Http\Request;

class StaffProfileController extends Controller
{
    public function store(Request $request, Restaurant $restaurant)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:owner_manager,cashier,kitchen'],
            'password' => ['required', 'string', 'min:4', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $restaurant->staffProfiles()->create([
            'name' => $data['name'],
            'role' => $data['role'],
            'password' => $data['password'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Staff profile created.');
    }

    public function update(Request $request, Restaurant $restaurant, StaffProfile $staffProfile)
    {
        abort_unless($staffProfile->restaurant_id === $restaurant->id, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', $staffProfile->role === 'owner_manager' ? 'in:owner_manager' : 'in:cashier,kitchen'],
            'password' => ['nullable', 'string', 'min:4', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $updates = [
            'name' => $data['name'],
            'role' => $staffProfile->role === 'owner_manager' ? 'owner_manager' : $data['role'],
            'is_active' => $request->boolean('is_active', true),
        ];

        if (! empty($data['password'])) {
            $updates['password'] = $data['password'];
        }

        $staffProfile->update($updates);

        return back()->with('success', 'Staff profile updated.');
    }

    public function destroy(Restaurant $restaurant, StaffProfile $staffProfile)
    {
        abort_unless($staffProfile->restaurant_id === $restaurant->id, 403);

        if ($staffProfile->role === 'owner_manager') {
            $ownerManagerCount = $restaurant->staffProfiles()->where('role', 'owner_manager')->count();
            abort_if($ownerManagerCount <= 1, 403, 'Cannot delete the last Owner/Manager profile.');
        }

        $staffProfile->delete();

        return back()->with('success', 'Staff profile deleted.');
    }
}
