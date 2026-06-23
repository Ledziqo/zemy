<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Support\ImageOptimizer;
use App\Support\PublicMenuCache;
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
            'logo' => ['nullable', 'image', 'max:4096'],
            'cropped_logo' => ['nullable', 'string', 'max:5600000'],
            'cover_image_path' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'service_charge_percentage' => ['nullable', 'numeric', 'min:0'],
            'vat_percentage' => ['nullable', 'numeric', 'min:0'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['in:cash,telebirr,cbe,awash,abyssinia'],
            'telebirr_number' => ['nullable', 'string', 'max:100'],
            'cbe_account_number' => ['nullable', 'string', 'max:100'],
            'awash_account_number' => ['nullable', 'string', 'max:100'],
            'abyssinia_account_number' => ['nullable', 'string', 'max:100'],
            'telebirr_qr' => ['nullable', 'image', 'max:4096'],
            'cbe_qr' => ['nullable', 'image', 'max:4096'],
            'awash_qr' => ['nullable', 'image', 'max:4096'],
            'abyssinia_qr' => ['nullable', 'image', 'max:4096'],
        ]);

        $settings = $restaurant->settings ?? [];
        if ($request->filled('cropped_logo')) {
            $data['logo_path'] = ImageOptimizer::storeDataUrl((string) $request->input('cropped_logo'), 'restaurants', 600);
        } elseif ($request->hasFile('logo')) {
            $data['logo_path'] = ImageOptimizer::storeUpload($request->file('logo'), 'restaurants', 600);
        }

        if ($request->hasFile('telebirr_qr')) {
            $data['telebirr_qr_path'] = ImageOptimizer::storeUpload($request->file('telebirr_qr'), 'payment-qr', 1000);
        }

        if ($request->hasFile('cbe_qr')) {
            $data['cbe_qr_path'] = ImageOptimizer::storeUpload($request->file('cbe_qr'), 'payment-qr', 1000);
        }

        if ($request->hasFile('awash_qr')) {
            $data['awash_qr_path'] = ImageOptimizer::storeUpload($request->file('awash_qr'), 'payment-qr', 1000);
        }

        if ($request->hasFile('abyssinia_qr')) {
            $data['abyssinia_qr_path'] = ImageOptimizer::storeUpload($request->file('abyssinia_qr'), 'payment-qr', 1000);
        }

        $restaurant->update([
            ...collect($data)->except(['service_charge_percentage', 'vat_percentage', 'payment_methods', 'telebirr_number', 'cbe_account_number', 'awash_account_number', 'abyssinia_account_number', 'telebirr_qr', 'telebirr_qr_path', 'cbe_qr', 'cbe_qr_path', 'awash_qr', 'awash_qr_path', 'abyssinia_qr', 'abyssinia_qr_path', 'logo', 'cropped_logo'])->all(),
            'settings' => array_merge($settings, [
                'service_charge_percentage' => $data['service_charge_percentage'] ?? 0,
                'vat_percentage' => $data['vat_percentage'] ?? 0,
                'payment_methods' => array_values($data['payment_methods'] ?? []),
                'telebirr_number' => $data['telebirr_number'] ?? null,
                'cbe_account_number' => $data['cbe_account_number'] ?? null,
                'awash_account_number' => $data['awash_account_number'] ?? null,
                'abyssinia_account_number' => $data['abyssinia_account_number'] ?? null,
                'telebirr_qr_path' => $data['telebirr_qr_path'] ?? ($settings['telebirr_qr_path'] ?? null),
                'cbe_qr_path' => $data['cbe_qr_path'] ?? ($settings['cbe_qr_path'] ?? null),
                'awash_qr_path' => $data['awash_qr_path'] ?? ($settings['awash_qr_path'] ?? null),
                'abyssinia_qr_path' => $data['abyssinia_qr_path'] ?? ($settings['abyssinia_qr_path'] ?? null),
            ]),
        ]);

        PublicMenuCache::bump($restaurant);

        return back()->with('success', 'Settings saved.');
    }
}
