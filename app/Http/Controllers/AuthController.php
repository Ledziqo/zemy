<?php

namespace App\Http\Controllers;

use App\Models\StaffProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->forget(['staff_profile_id', 'staff_profile_role', 'staff_profile_name']);

        return match (Auth::user()->role) {
            'admin' => redirect()->route('admin.dashboard'),
            default => redirect()->route('restaurant.profile-select'),
        };
    }

    public function showProfileSelect(Request $request)
    {
        $restaurant = $request->user()->restaurant;
        abort_unless($restaurant, 403);

        $profiles = $restaurant->staffProfiles()
            ->where('is_active', true)
            ->when(! $restaurant->kitchenScreenEnabled(), fn ($query) => $query->where('role', '!=', 'kitchen'))
            ->orderByRaw("CASE role WHEN 'owner_manager' THEN 1 WHEN 'cashier' THEN 2 WHEN 'kitchen' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->get();

        return view('auth.profile-select', [
            'restaurant' => $restaurant,
            'profiles' => $profiles,
        ]);
    }

    public function profileLogin(Request $request)
    {
        $credentials = $request->validate([
            'profile_id' => ['required', 'integer'],
            'password' => ['required', 'string'],
        ]);

        $restaurant = $request->user()->restaurant;
        abort_unless($restaurant, 403);
        $profile = StaffProfile::where('id', $credentials['profile_id'])
            ->where('restaurant_id', $restaurant->id)
            ->where('is_active', true)
            ->first();

        if (! $profile || ! in_array($profile->role, StaffProfile::ROLES, true)
            || ($profile->role === 'kitchen' && ! $restaurant->kitchenScreenEnabled())
            || ! password_verify($credentials['password'], $profile->password)) {
            return back()->withErrors(['password' => 'Incorrect or unavailable profile.'])->withInput(['profile_id' => $credentials['profile_id']]);
        }

        $request->session()->regenerate();
        $request->session()->put('staff_profile_id', $profile->id);
        $request->session()->put('staff_profile_role', $profile->role);
        $request->session()->put('staff_profile_name', $profile->name);

        return redirect()->route('restaurant.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
