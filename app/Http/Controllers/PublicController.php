<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use App\Models\Restaurant;
use App\Support\EmailValidation;
use App\Support\PublicSitemapCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class PublicController extends Controller
{
    public function landing()
    {
        return view('public.landing');
    }

    public function storeDemoRequest(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'restaurant_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', Rule::in(['restaurant', 'hotel'])],
            'phone' => ['required', 'string', 'max:50'],
            'email' => EmailValidation::rules(false),
            'location' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! Schema::hasColumn('demo_requests', 'business_type')) {
            unset($data['business_type']);
        }

        DemoRequest::create($data);

        return back()->with('success', __('Thanks. ZemTab will contact you shortly.'));
    }

    public function sitemap()
    {
        $xml = Cache::store('file')->remember(PublicSitemapCache::key(), now()->addMinutes(10), function () {
            $restaurants = Restaurant::where('is_active', true)
                ->with(['tables' => fn ($query) => $query->where('is_active', true)])
                ->get();
            return view('public.sitemap', compact('restaurants'))->render();
        });

        return Response::make($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
