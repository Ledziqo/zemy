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
            'sections' => ['required', 'array', 'min:1', 'max:100'],
            'sections.*' => ['required', 'array:title,body'],
            'sections.*.title' => ['required', 'string', 'max:200'],
            'sections.*.body' => ['required', 'string', 'max:20000'],
        ])->validate();
        DB::transaction(function () use ($data, $board) {
            $restaurant = Restaurant::whereKey($data['restaurant_id'])->lockForUpdate()->firstOrFail();
            $settings = $restaurant->settings ?? [];
            $settings['information_board'] = $board;
            $restaurant->update(['settings' => $settings]);
        });
        return back()->with('information_import_output', 'Information board imported successfully for the selected restaurant.');
    }
}
