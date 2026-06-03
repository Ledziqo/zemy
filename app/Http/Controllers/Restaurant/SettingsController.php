<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'logo' => ['nullable', 'image', 'max:4096'],
            'cover_image_path' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'service_charge_percentage' => ['nullable', 'numeric', 'min:0'],
            'vat_percentage' => ['nullable', 'numeric', 'min:0'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['in:cash,telebirr,cbe'],
            'telebirr_number' => ['nullable', 'string', 'max:100'],
            'cbe_account_number' => ['nullable', 'string', 'max:100'],
        ]);

        $settings = $restaurant->settings ?? [];
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads/restaurants'), $filename);
            $data['logo_path'] = 'uploads/restaurants/'.$filename;
        }

        $restaurant->update([
            ...collect($data)->except(['service_charge_percentage', 'vat_percentage', 'payment_methods', 'telebirr_number', 'cbe_account_number', 'logo'])->all(),
            'settings' => array_merge($settings, [
                'service_charge_percentage' => $data['service_charge_percentage'] ?? 0,
                'vat_percentage' => $data['vat_percentage'] ?? 0,
                'payment_methods' => array_values($data['payment_methods'] ?? []),
                'telebirr_number' => $data['telebirr_number'] ?? null,
                'cbe_account_number' => $data['cbe_account_number'] ?? null,
            ]),
        ]);

        return back()->with('success', 'Settings saved.');
    }
}
