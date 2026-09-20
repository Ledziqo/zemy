<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use App\Support\PublicMenuCache;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;

class TableController extends Controller
{
    private function restaurant(Request $request) { return $request->user()->restaurant; }

    public function index(Request $request)
    {
        $restaurant = $this->restaurant($request);
        return view('restaurant.tables.index', ['restaurant' => $restaurant, 'tables' => $restaurant->tables()->orderByRaw('CAST(table_number AS UNSIGNED)')->paginate(50)]);
    }

    public function setupPack(Request $request)
    {
        $restaurant = $this->restaurant($request);
        $tables = $restaurant->tables()->where('is_active', true)->orderByRaw('CAST(table_number AS UNSIGNED)')->get();
        $sticker = array_merge($this->defaultStickerSettings($restaurant), $restaurant->settings['qr_sticker'] ?? []);

        // Reuse the page's database connection; separate authenticated QR
        // requests consume the host's hourly connection allowance per image.
        $qrImages = $tables->mapWithKeys(fn (RestaurantTable $table) => [
            $table->id => 'data:image/svg+xml;base64,'.base64_encode($this->buildQr($restaurant, $table)->getString()),
        ]);
        $tables->each(fn (RestaurantTable $table) => $table->setRelation('restaurant', $restaurant));

        return view('restaurant.tables.setup_pack', [
            'restaurant' => $restaurant,
            'tables' => $tables,
            'qrImages' => $qrImages,
            'sticker' => $sticker,
        ]);
    }

    public function store(Request $request)
    {
        $restaurant = $this->restaurant($request);
        $table = $restaurant->tables()->create($this->validated($request));
        PublicMenuCache::bump($restaurant);
        return back()->with('success', $table->locationTypeLabel().' added.');
    }

    public function update(Request $request, RestaurantTable $table)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($table->restaurant_id === $restaurant->id, 403);
        $table->update($this->validated($request));
        PublicMenuCache::bump($restaurant);
        return back()->with('success', $table->locationTypeLabel().' updated.');
    }

    public function destroy(Request $request, RestaurantTable $table)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($table->restaurant_id === $restaurant->id, 403);
        $table->delete();
        PublicMenuCache::bump($restaurant);
        return back()->with('success', $restaurant->locationLabelTitle().' deleted.');
    }

    public function qr(Request $request, RestaurantTable $table)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($table->restaurant_id === $restaurant->id, 403);

        $result = $this->buildQr($restaurant, $table);

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'inline; filename="zemtab-'.$restaurant->slug.'-'.strtolower($table->locationTypeLabel()).'-'.$table->table_number.'.svg"',
        ]);
    }

    private function buildQr($restaurant, RestaurantTable $table)
    {
        $sticker = array_merge($this->defaultStickerSettings($restaurant), $restaurant->settings['qr_sticker'] ?? []);

        return (new Builder(
            writer: new SvgWriter(),
            data: route('menu.show', [$restaurant->slug, $table->table_number]),
            size: 500,
            margin: 20,
            foregroundColor: $this->qrColor($sticker['qr_color']),
            backgroundColor: $this->qrColor($sticker['qr_background_color']),
        ))->build();
    }

    private function qrColor(string $hex): Color
    {
        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
            $hex = '#111111';
        }

        return new Color(
            hexdec(substr($hex, 1, 2)),
            hexdec(substr($hex, 3, 2)),
            hexdec(substr($hex, 5, 2)),
        );
    }

    private function defaultStickerSettings($restaurant = null): array
    {
        return [
            'background_color' => '#FFFFFF',
            'border_color' => '#111111',
            'text_color' => '#111111',
            'accent_color' => $restaurant?->primary_color ?: '#D22630',
            'qr_color' => '#111111',
            'qr_background_color' => '#FFFFFF',
            'design' => 'classic',
            'table_scan_text' => 'SCAN TO ORDER',
            'room_scan_text' => 'SCAN FOR ROOM SERVICE',
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'table_number' => ['required', 'string', 'max:50'],
            'location_type' => ['required', 'in:table,room'],
            'table_name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
