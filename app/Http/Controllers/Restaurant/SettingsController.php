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

        if ($request->hasFile('telebirr_qr')) {
            $data['telebirr_qr_path'] = $this->storeUploadedImage($request->file('telebirr_qr'), 'payment-qr');
        }

        if ($request->hasFile('cbe_qr')) {
            $data['cbe_qr_path'] = $this->storeUploadedImage($request->file('cbe_qr'), 'payment-qr');
        }

        if ($request->hasFile('awash_qr')) {
            $data['awash_qr_path'] = $this->storeUploadedImage($request->file('awash_qr'), 'payment-qr');
        }

        if ($request->hasFile('abyssinia_qr')) {
            $data['abyssinia_qr_path'] = $this->storeUploadedImage($request->file('abyssinia_qr'), 'payment-qr');
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

        if (strlen($bytes) > 4 * 1024 * 1024 || @getimagesizefromstring($bytes) === false) {
            abort(422, 'The cropped logo must be a valid image no larger than 4 MB.');
        }

        $directory = public_path('uploads/restaurants');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = Str::uuid().'.'.$extension;
        file_put_contents($directory.'/'.$filename, $bytes);

        return 'uploads/restaurants/'.$filename;
    }

    private function storeUploadedImage($file, string $folder): string
    {
        $directory = public_path('uploads/'.$folder);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'uploads/'.$folder.'/'.$filename;
    }
}
