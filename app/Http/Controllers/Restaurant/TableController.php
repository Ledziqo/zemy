<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use App\Support\ImageOptimizer;
use App\Support\PublicMenuCache;
use App\Support\QrSetupPackStore;
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
        $tables = $restaurant->tables()->orderByRaw('CAST(table_number AS UNSIGNED)')->paginate(50);
        $sticker = array_merge($this->defaultStickerSettings($restaurant), $restaurant->settings['qr_sticker'] ?? []);
        $previewTable = $tables->first();
        $previewQr = $previewTable ? 'data:image/svg+xml;base64,'.base64_encode($this->buildQr($restaurant, $previewTable)->getString()) : null;
        return view('restaurant.tables.index', compact('restaurant', 'tables', 'sticker', 'previewTable', 'previewQr'));
    }

    public function saveDesign(Request $request)
    {
        $rules = [];
        foreach (['background_color', 'border_color', 'text_color', 'accent_color'] as $key) {
            $rules[$key] = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
        }
        foreach (['logo_size' => [1, 200], 'logo_width' => [1, 200], 'logo_x' => [-200, 300], 'logo_y' => [-200, 400], 'heading_x' => [-300, 300], 'heading_y' => [-300, 400], 'kicker_x' => [-300, 300], 'kicker_y' => [-300, 400], 'title_x' => [-300, 300], 'title_y' => [-300, 400], 'location_x' => [-300, 300], 'location_y' => [-300, 400], 'scan_x' => [-300, 300], 'scan_y' => [-300, 400], 'frame_x' => [-300, 300], 'frame_y' => [-300, 400], 'hint_x' => [-300, 300], 'hint_y' => [-300, 400], 'footer_x' => [-300, 300], 'footer_y' => [-300, 400], 'text_size' => [14, 22], 'qr_size' => [38, 50], 'detail_size' => [6, 9], 'footer_size' => [50, 200], 'art_opacity' => [10, 100]] as $key => [$min, $max]) {
            $rules[$key] = ['required', 'integer', "between:$min,$max"];
        }
        $rules['table_scan_text'] = ['required', 'string', 'max:40'];
        $rules['elements'] = ['sometimes', 'array'];
        foreach (['logo','cross','line_left','line_right','kicker_text','title','location','frame','hint','footer','credit','zemtab','art'] as $element) {
            $rules['elements.'.$element] = ['sometimes', 'array:x,y,sx,sy'];
            foreach (['x','y','sx','sy'] as $dimension) {
                $rules['elements.'.$element.'.'.$dimension] = ['sometimes', 'numeric', str_starts_with($dimension, 's') ? 'between:0.02,20' : 'between:-1000,1000'];
            }
        }
        $rules['room_scan_text'] = ['required', 'string', 'max:40'];
        $rules['qr_logo'] = ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:4096'];
        $rules['remove_qr_logo'] = ['nullable', 'boolean'];
        $data = $request->validate($rules);
        // These controls are request-only; never store the UploadedFile or the
        // checkbox itself inside the JSON settings column.
        unset($data['qr_logo'], $data['remove_qr_logo']);
        $restaurant = $this->restaurant($request);
        $settings = $restaurant->settings ?? [];
        if ($request->hasFile('qr_logo')) {
            $data['qr_logo_path'] = ImageOptimizer::storeUpload($request->file('qr_logo'), 'restaurants/qr-logos', 800);
        } elseif ($request->boolean('remove_qr_logo')) {
            $data['qr_logo_path'] = null;
        }
        // Keep the QR itself high contrast regardless of the decorative palette.
        $settings['qr_sticker'] = array_merge($settings['qr_sticker'] ?? [], $data, ['qr_color' => '#111111', 'qr_background_color' => '#FFFFFF']);
        $restaurant->update(['settings' => $settings]);
        QrSetupPackStore::invalidate((int) $restaurant->id);
        return redirect()->route('restaurant.tables.index', [], 303)->with('success', 'QR design saved. Open the setup pack to print your updated cards.');
    }

    public function setupPack(Request $request)
    {
        $restaurant = $this->restaurant($request);
        if ($request->has('page')) {
            return $this->setupPackBatch($request);
        }

        if ($url = QrSetupPackStore::currentUrl((int) $restaurant->id)) {
            return redirect()->away($url);
        }

        $sticker = array_merge($this->defaultStickerSettings($restaurant), $restaurant->settings['qr_sticker'] ?? []);
        $tableCount = $restaurant->tables()->where('is_active', true)->count();
        $buildToken = QrSetupPackStore::begin($restaurant, $tableCount);

        return view('restaurant.tables.setup_pack', [
            'restaurant' => $restaurant,
            'sticker' => $sticker,
            'tableCount' => $tableCount,
            'batchSize' => 12,
            'buildToken' => $buildToken,
        ]);
    }

    public function setupPackBatch(Request $request)
    {
        $restaurant = $this->restaurant($request);
        $page = max(0, (int) $request->input('page', 0));
        $batchSize = 12;
        $tables = $restaurant->tables()
            ->where('is_active', true)
            ->orderByRaw('CAST(table_number AS UNSIGNED)')
            ->skip($page * $batchSize)
            ->take($batchSize)
            ->get();
        $sticker = array_merge($this->defaultStickerSettings($restaurant), $restaurant->settings['qr_sticker'] ?? []);
        $qrImages = $tables->mapWithKeys(fn (RestaurantTable $table) => [
            $table->id => 'data:image/svg+xml;base64,'.base64_encode($this->cachedQrSvg($restaurant, $table)),
        ]);
        $tables->each(fn (RestaurantTable $table) => $table->setRelation('restaurant', $restaurant));

        $html = view('restaurant.tables.setup_pack_batch', compact('restaurant', 'tables', 'qrImages', 'sticker'))->render();
        QrSetupPackStore::storePage($restaurant, (string) $request->query('build'), $page, $html);

        return response($html)->header('Cache-Control', 'private, no-store');
    }

    public function publishSetupPack(Request $request)
    {
        $restaurant = $this->restaurant($request);
        $data = $request->validate([
            'build' => ['required', 'regex:/^[A-Za-z0-9]{48}$/'],
            'page_count' => ['required', 'integer', 'between:1,1000'],
            'shell' => ['required', 'string', 'max:500000'],
        ]);

        return response()->json([
            'url' => QrSetupPackStore::publish($restaurant, $data['build'], $data['shell'], (int) $data['page_count']),
        ])->header('Cache-Control', 'no-store');
    }

    public function servePreparedPack(int $restaurantId, string $token)
    {
        $html = QrSetupPackStore::preparedHtml($restaurantId, $token);
        abort_unless($html !== null, 404);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    public function store(Request $request)
    {
        $restaurant = $this->restaurant($request);
        $table = $restaurant->tables()->create($this->validated($request));
        $this->cachedQrSvg($restaurant, $table);
        PublicMenuCache::bump($restaurant);
        QrSetupPackStore::invalidate((int) $restaurant->id);
        return back()->with('success', $table->locationTypeLabel().' added.');
    }

    public function update(Request $request, RestaurantTable $table)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($table->restaurant_id === $restaurant->id, 403);
        $table->update($this->validated($request));
        $this->cachedQrSvg($restaurant, $table);
        PublicMenuCache::bump($restaurant);
        QrSetupPackStore::invalidate((int) $restaurant->id);
        return back()->with('success', $table->locationTypeLabel().' updated.');
    }

    public function destroy(Request $request, RestaurantTable $table)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($table->restaurant_id === $restaurant->id, 403);
        $table->delete();
        PublicMenuCache::bump($restaurant);
        QrSetupPackStore::invalidate((int) $restaurant->id);
        return back()->with('success', $restaurant->locationLabelTitle().' deleted.');
    }

    public function qr(Request $request, RestaurantTable $table)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($table->restaurant_id === $restaurant->id, 403);

        $result = $this->cachedQrSvg($restaurant, $table);

        return response($result, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'inline; filename="zemtab-'.$restaurant->slug.'-'.strtolower($table->locationTypeLabel()).'-'.$table->table_number.'.svg"',
        ]);
    }

    private function cachedQrSvg($restaurant, RestaurantTable $table): string
    {
        $sticker = array_merge($this->defaultStickerSettings($restaurant), $restaurant->settings['qr_sticker'] ?? []);
        $signature = sha1(route('menu.show', [$restaurant->slug, $table->table_number]).'|'.$sticker['qr_color'].'|'.$sticker['qr_background_color']);
        $relativePath = 'uploads/qr-codes/'.$restaurant->id.'/'.$table->id.'-'.$signature.'.svg';
        $absolutePath = public_path($relativePath);

        if (is_file($absolutePath)) {
            $svg = @file_get_contents($absolutePath);
            if ($svg !== false) {
                return $svg;
            }
        }

        $svg = $this->buildQr($restaurant, $table)->getString();
        $directory = dirname($absolutePath);
        if (! is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }
        if (is_dir($directory) && @file_put_contents($absolutePath, $svg, LOCK_EX) !== false) {
        }

        return $svg;
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
            'logo_size' => 24,
            'logo_width' => 53,
            'logo_x' => 37,
            'logo_y' => 18,
            'heading_x' => 0,
            'heading_y' => 0,
            'kicker_x' => 0,
            'kicker_y' => 0,
            'title_x' => 0,
            'title_y' => 0,
            'location_x' => 0,
            'location_y' => 0,
            'scan_x' => 0,
            'scan_y' => -3,
            'frame_x' => 0,
            'frame_y' => 0,
            'hint_x' => 0,
            'hint_y' => 0,
            'footer_x' => 0,
            'footer_y' => 0,
            'text_size' => 18,
            'qr_size' => 46,
            'detail_size' => 7,
            'footer_size' => 100,
            'art_opacity' => 100,
            'qr_logo_path' => null,
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
