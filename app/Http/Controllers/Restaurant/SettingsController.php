<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        return view('restaurant.settings.edit', ['restaurant' => $request->user()->restaurant]);
    }

    public function update(Request $request)
    {
        $restaurant = $request->user()->restaurant;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255', 'unique:restaurants,slug,'.$restaurant->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'cover_image_path' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'service_charge_percentage' => ['nullable', 'numeric', 'min:0'],
            'vat_percentage' => ['nullable', 'numeric', 'min:0'],
        ]);

        $restaurant->update([
            ...collect($data)->except(['service_charge_percentage', 'vat_percentage'])->all(),
            'settings' => [
                'service_charge_percentage' => $data['service_charge_percentage'] ?? 0,
                'vat_percentage' => $data['vat_percentage'] ?? 0,
            ],
        ]);

        return back()->with('success', 'Settings saved.');
    }
}
