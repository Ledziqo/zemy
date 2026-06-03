<?php

namespace App\Http\Controllers;

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

        $user = Auth::user();
        $restaurantAccessStatus = $user->restaurant?->dashboard_access_status ?? 'active';

        return match ($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'restaurant_owner', 'staff' => $restaurantAccessStatus === 'active'
                ? redirect()->route('restaurant.dashboard')
                : redirect()->route('restaurant.access-required'),
            default => redirect()->route('restaurant.dashboard'),
        };
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
