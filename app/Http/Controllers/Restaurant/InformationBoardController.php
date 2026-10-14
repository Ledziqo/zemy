<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Support\PublicMenuCache;
use App\Support\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InformationBoardController extends Controller
{
    public function edit(Request $request)
    {
        $restaurant = $request->user()->restaurant;
        abort_unless($restaurant->informationBoardEnabled(), 403);
        return view('restaurant.information.edit', [
            'restaurant' => $restaurant,
            'board' => $restaurant->settings['information_board'] ?? [
                'title' => 'Hotel Guide', 'sections' => [['title' => 'Welcome', 'body' => $restaurant->settings['guest_information'] ?? '']],
            ],
            'previewUrl' => PublicMenuCache::informationUrl($restaurant),
        ]);
    }

    public function update(Request $request)
    {
        $restaurant = $request->user()->restaurant;
        abort_unless($restaurant->informationBoardEnabled(), 403);
        $data = $request->validate([
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'logo_width' => ['required', 'integer', 'between:80,400'],
            'title' => ['required', 'string', 'max:120'],
            'sections' => ['required', 'array', 'min:1', 'max:100'],
            'sections.*' => ['required', 'array:title,body'],
            'sections.*.title' => ['required', 'string', 'max:200'],
            'sections.*.body' => ['required', 'string', 'max:20000'],
        ]);
        unset($data['logo']);
        if ($request->hasFile('logo')) $data['logo_path'] = ImageOptimizer::storeUpload($request->file('logo'), 'information-logos', 2000, 95);
        DB::transaction(function () use ($restaurant, $data) {
            $locked = Restaurant::whereKey($restaurant->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->informationBoardEnabled(), 403);
            $settings = $locked->settings ?? [];
            $settings['information_board'] = array_merge($settings['information_board'] ?? [], $data, [
                'format' => 'zemtab-information', 'version' => 1, 'sections' => array_values($data['sections']),
            ]);
            $locked->update(['settings' => $settings]);
        });
        return redirect()->route('restaurant.settings.edit', ['information_board' => 1])->with('success', 'Information board saved. Your information QR now shows these changes.');
    }
}
