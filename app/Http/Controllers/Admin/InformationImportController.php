<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InformationImportController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'restaurant_id' => ['required', 'integer', Rule::exists('restaurants', 'id')],
            'information_package' => ['required', 'file', 'max:2048', 'extensions:json'],
        ]);
        try {
            $board = json_decode(file_get_contents($request->file('information_package')->getRealPath()), true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            throw ValidationException::withMessages(['information_package' => 'The file must contain valid JSON.']);
        }
        if (!is_array($board)) throw ValidationException::withMessages(['information_package' => 'Invalid information package.']);
        $board = Validator::make($board, [
            'format' => ['required', Rule::in(['zemtab-information'])],
            'version' => ['required', 'integer', Rule::in([1])],
            'title' => ['required', 'string', 'max:120'],
            'logo_data' => ['nullable', 'string', 'max:1500000'],
            'sections' => ['required', 'array', 'min:1', 'max:100'],
            'sections.*' => ['required', 'array:title,body'],
            'sections.*.title' => ['required', 'string', 'max:200'],
            'sections.*.body' => ['required', 'string', 'max:20000'],
        ])->validate();
        $logoBytes = null;
        if (!empty($board['logo_data'])) {
            $logoBytes = str_starts_with($board['logo_data'], 'data:image/png;base64,')
                ? base64_decode(substr($board['logo_data'], 22), true) : false;
            $image = is_string($logoBytes) ? @getimagesizefromstring($logoBytes) : false;
            if (!$image || ($image['mime'] ?? '') !== 'image/png' || $image[0] > 4096 || $image[1] > 4096) {
                throw ValidationException::withMessages(['information_package' => 'The embedded logo must be a valid PNG up to 4096 pixels.']);
            }
        }
        unset($board['logo_data']);
        if ($logoBytes !== null) {
            $directory = public_path('uploads/information-logos');
            if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw ValidationException::withMessages(['information_package' => 'Could not create the information logo directory.']);
            }
            $board['logo_path'] = 'uploads/information-logos/'.hash('sha256', $logoBytes).'.png';
            if (file_put_contents(public_path($board['logo_path']), $logoBytes, LOCK_EX) === false) {
                throw ValidationException::withMessages(['information_package' => 'Could not save the information logo.']);
            }
        }
        DB::transaction(function () use ($data, $board) {
            $restaurant = Restaurant::whereKey($data['restaurant_id'])->lockForUpdate()->firstOrFail();
            $settings = $restaurant->settings ?? [];
            $settings['information_board'] = $board;
            $restaurant->update(['settings' => $settings]);
        });
        return back()->with('information_import_output', 'Information board imported successfully for the selected restaurant.');
    }
}
