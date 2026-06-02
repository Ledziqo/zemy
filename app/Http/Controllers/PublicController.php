<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function landing()
    {
        return view('public.landing');
    }

    public function storeDemoRequest(Request $request)
    {
        DemoRequest::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'restaurant_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]));

        return back()->with('success', 'Thanks. ZemTab will contact you shortly.');
    }
}
