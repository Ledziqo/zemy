<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function index()
    {
        return view('admin.restaurants.index', [
            'restaurants' => Restaurant::latest()->paginate(50),
            'owners' => User::whereIn('role', ['restaurant_owner', 'staff'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Restaurant::create($this->validated($request));
        return back()->with('success', 'Restaurant created.');
    }

    public function update(Request $request, Restaurant $restaurant)
    {
        $restaurant->update($this->validated($request));
        return back()->with('success', 'Restaurant updated.');
    }

    public function destroy(Restaurant $restaurant)
    {
        $restaurant->delete();
        return back()->with('success', 'Restaurant deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
