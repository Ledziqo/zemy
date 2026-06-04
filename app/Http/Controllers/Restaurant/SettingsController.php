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
            'cropped_logo' => ['nullable', 'string'],
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
        if ($request->filled('cropped_logo')) {
            $data['logo_path'] = $this->storeCroppedLogo((string) $request->input('cropped_logo'));
        } elseif ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
            if (! is_dir(public_path('uploads/restaurants'))) {
                mkdir(public_path('uploads/restaurants'), 0755, true);
            }
            $file->move(public_path('uploads/restaurants'), $filename);
            $data['logo_path'] = 'uploads/restaurants/'.$filename;
        }

        $restaurant->update([
            ...collect($data)->except(['service_charge_percentage', 'vat_percentage', 'payment_methods', 'telebirr_number', 'cbe_account_number', 'logo', 'cropped_logo'])->all(),
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

    private function storeCroppedLogo(string $dataUrl): string
    {
        if (! preg_match('/^data:image\/(png|jpe?g|webp);base64,(.+)$/', $dataUrl, $matches)) {
            abort(422, 'Invalid logo crop data.');
        }

        $extension = match ($matches[1]) {
            'jpeg', 'jpg' => 'jpg',
            'webp' => 'webp',
            default => 'png',
        };
        $bytes = base64_decode($matches[2], true);

        if ($bytes === false) {
            abort(422, 'Invalid logo crop data.');
        }

        $directory = public_path('uploads/restaurants');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = Str::uuid().'.'.$extension;
        file_put_contents($directory.'/'.$filename, $bytes);

        return 'uploads/restaurants/'.$filename;
    }
}
