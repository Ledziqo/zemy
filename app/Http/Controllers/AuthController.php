<?php

namespace App\Http\Controllers;

use App\Models\StaffProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

        return match (Auth::user()->role) {
            'admin' => redirect()->route('admin.dashboard'),
            default => redirect()->route('restaurant.profile-select'),
        };
    }

    public function showProfileSelect(Request $request)
    {
        $restaurant = $request->user()->restaurant;
        abort_unless($restaurant, 403);

        if (! $restaurant->staffProfiles()->where('is_active', true)->exists()) {
            foreach ([
                ['name' => 'Owner/Manager', 'role' => 'owner_manager'],
                ['name' => 'Cashier', 'role' => 'cashier'],
                ['name' => 'Kitchen', 'role' => 'kitchen'],
            ] as $profile) {
                $restaurant->staffProfiles()->updateOrCreate(
                    ['role' => $profile['role']],
                    [
                        'name' => $profile['name'],
                        'password' => Hash::make('password'),
                        'is_active' => true,
                    ]
                );
            }
        }

        $profiles = $restaurant->staffProfiles()->where('is_active', true)->orderByRaw("CASE role WHEN 'owner_manager' THEN 1 WHEN 'cashier' THEN 2 WHEN 'kitchen' THEN 3 ELSE 4 END")->orderBy('name')->get();

        return view('auth.profile-select', [
            'restaurant' => $restaurant,
            'profiles' => $profiles,
        ]);
    }

    public function profileLogin(Request $request)
    {
        $credentials = $request->validate([
            'profile_id' => ['required', 'exists:staff_profiles,id'],
            'password' => ['required'],
        ]);

        $restaurant = $request->user()->restaurant;
        $profile = StaffProfile::where('id', $credentials['profile_id'])
            ->where('restaurant_id', $restaurant->id)
            ->where('is_active', true)
            ->firstOrFail();

        if (! password_verify($credentials['password'], $profile->password)) {
            return back()->withErrors(['password' => 'Incorrect profile password.'])->withInput(['profile_id' => $profile->id]);
        }

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
