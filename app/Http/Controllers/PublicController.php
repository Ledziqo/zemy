<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

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

        return back()->with('success', __('Thanks. ZemTab will contact you shortly.'));
    }

    public function sitemap()
    {
        $restaurants = Restaurant::where('is_active', true)
            ->with(['tables' => fn ($query) => $query->where('is_active', true)])
            ->get();

        $xml = view('public.sitemap', compact('restaurants'))->render();

        return Response::make($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
