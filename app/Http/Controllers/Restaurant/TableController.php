<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use App\Support\ImageOptimizer;
use App\Support\PublicMenuCache;
use App\Support\PublicSitemapCache;
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
        $designs = $this->qrCardDesigns($restaurant);
        $activeTables = $restaurant->tables()->where('is_active', true)->get();
        $activeTables->each(fn (RestaurantTable $table) => $table->setRelation('restaurant', $restaurant));
        $previewCards = [];
        foreach (['table', 'room'] as $type) {
            $previewTable = $activeTables->first(fn (RestaurantTable $table) => $type === 'room' ? $table->isRoomServicePoint() : ! $table->isRoomServicePoint());
            $previewCards[$type] = [
                'qr' => $previewTable ? 'data:image/svg+xml;base64,'.base64_encode($this->buildQr($restaurant, $previewTable)->getString()) : null,
                'label' => $previewTable?->displayLabel() ?? ($type === 'room' ? 'Room 204' : 'Table 1'),
            ];
        }
        $designType = in_array(old('design_type', 'table'), ['table', 'room'], true) ? old('design_type', 'table') : 'table';
        $sticker = $designs[$designType] ?? $designs['table'];
        return view('restaurant.tables.index', compact('restaurant', 'tables', 'sticker', 'previewCards', 'designs', 'designType'));
    }

    public function saveDesign(Request $request)
    {
        $rules = [];
        foreach (['background_color', 'border_color', 'text_color', 'accent_color'] as $key) {
            $rules[$key] = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
        }
        $rules['orientation'] = ['required', 'in:portrait,landscape'];
        foreach (['logo_size' => [1, 200], 'logo_width' => [1, 200], 'logo_x' => [-200, 300], 'logo_y' => [-200, 400], 'heading_x' => [-300, 300], 'heading_y' => [-300, 400], 'kicker_x' => [-300, 300], 'kicker_y' => [-300, 400], 'title_x' => [-300, 300], 'title_y' => [-300, 400], 'location_x' => [-300, 300], 'location_y' => [-300, 400], 'scan_x' => [-300, 300], 'scan_y' => [-300, 400], 'frame_x' => [-300, 300], 'frame_y' => [-300, 400], 'hint_x' => [-300, 300], 'hint_y' => [-300, 400], 'footer_x' => [-300, 300], 'footer_y' => [-300, 400], 'text_size' => [14, 22], 'qr_size' => [38, 50], 'detail_size' => [6, 9], 'footer_size' => [50, 200], 'art_opacity' => [10, 100]] as $key => [$min, $max]) {
            $rules[$key] = ['required', 'integer', "between:$min,$max"];
        }
        $rules['design_type'] = ['required', 'in:table,room'];
        $rules['scan_text'] = ['required', 'string', 'max:40'];
        $rules['elements'] = ['sometimes', 'array'];
        foreach (['logo','cross','line_left','line_right','kicker_text','title','location','frame','hint','footer','credit','zemtab','art'] as $element) {
            $rules['elements.'.$element] = ['sometimes', 'array:x,y,sx,sy'];
            foreach (['x','y','sx','sy'] as $dimension) {
                $rules['elements.'.$element.'.'.$dimension] = ['sometimes', 'numeric', str_starts_with($dimension, 's') ? 'between:0.02,20' : 'between:-1000,1000'];
            }
        }
        $rules['qr_logo'] = ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:4096'];
        $rules['remove_qr_logo'] = ['nullable', 'boolean'];
        $data = $request->validate($rules);
        // These controls are request-only; never store the UploadedFile or the
        // checkbox itself inside the JSON settings column.
        unset($data['qr_logo'], $data['remove_qr_logo']);
        $restaurant = $this->restaurant($request);
        $settings = $restaurant->settings ?? [];
        $type = $data['design_type'];
        unset($data['design_type']);
        $scanText = $data['scan_text'];
        unset($data['scan_text']);
        $qrSticker = $settings['qr_sticker'] ?? [];
        $designs = $qrSticker['designs'] ?? [];
        $current = array_merge($this->defaultStickerSettings($restaurant), $qrSticker, $designs[$type] ?? []);
        unset($current['designs']);
        $data[$type.'_scan_text'] = $scanText;
        if ($request->hasFile('qr_logo')) {
            $data['qr_logo_path'] = ImageOptimizer::storeUpload($request->file('qr_logo'), 'restaurants/qr-logos', 2000, 95);
        } elseif ($request->boolean('remove_qr_logo')) {
            $data['qr_logo_path'] = null;
        }
        // Keep the QR itself high contrast regardless of the decorative palette.
        $qrSticker['designs'][$type] = array_merge($current, $data);
        $qrSticker['qr_color'] = '#111111';
        $qrSticker['qr_background_color'] = '#FFFFFF';
        $settings['qr_sticker'] = $qrSticker;
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

        $designs = $this->qrCardDesigns($restaurant);
        $tables = $restaurant->tables()->where('is_active', true)->get();
        $tables->each(fn (RestaurantTable $table) => $table->setRelation('restaurant', $restaurant));
        $tableCount = $tables->count();
        $roomCount = $tables->filter(fn (RestaurantTable $table) => $table->isRoomServicePoint())->count();
        $pageCount = (int) ceil(($tableCount - $roomCount) / 8) + (int) ceil($roomCount / 8);
        $buildToken = QrSetupPackStore::begin($restaurant, $pageCount * 8);

        return view('restaurant.tables.setup_pack', [
            'restaurant' => $restaurant,
            'sticker' => $designs['table'],
            'tableCount' => $tableCount,
            'pageCount' => $pageCount,
            'roomCount' => $roomCount,
            'tableOnlyCount' => $tableCount - $roomCount,
            'batchSize' => 8,
            'buildToken' => $buildToken,
        ]);
    }

    public function setupPackBatch(Request $request)
    {
        $restaurant = $this->restaurant($request);
        $page = max(0, (int) $request->input('page', 0));
        $batchSize = 8;
        $allTables = $restaurant->tables()
            ->where('is_active', true)
            ->orderByRaw('CAST(table_number AS UNSIGNED)')
            ->get();
        $allTables->each(fn (RestaurantTable $table) => $table->setRelation('restaurant', $restaurant));
        $tableCards = $allTables->filter(fn (RestaurantTable $table) => ! $table->isRoomServicePoint())->values();
        $roomCards = $allTables->filter(fn (RestaurantTable $table) => $table->isRoomServicePoint())->values();
        $tablePageCount = (int) ceil($tableCards->count() / $batchSize);
        $type = $page < $tablePageCount ? 'table' : 'room';
        $typePage = $type === 'table' ? $page : $page - $tablePageCount;
        $tables = ($type === 'table' ? $tableCards : $roomCards)->slice($typePage * $batchSize, $batchSize)->values();
        $designs = $this->qrCardDesigns($restaurant);
        $sticker = $designs[$type];
        $qrImages = $tables->mapWithKeys(fn (RestaurantTable $table) => [
            $table->id => 'data:image/svg+xml;base64,'.base64_encode($this->cachedQrSvg($restaurant, $table)),
        ]);
        $html = view('restaurant.tables.setup_pack_batch', compact('restaurant', 'tables', 'qrImages', 'sticker', 'type'))->render();
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
        PublicSitemapCache::forget();
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
        PublicSitemapCache::forget();
        QrSetupPackStore::invalidate((int) $restaurant->id);
        return back()->with('success', $table->locationTypeLabel().' updated.');
    }

    public function destroy(Request $request, RestaurantTable $table)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($table->restaurant_id === $restaurant->id, 403);
        $table->delete();
        PublicMenuCache::bump($restaurant);
        PublicSitemapCache::forget();
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
            'orientation' => 'portrait',
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

    private function qrCardDesigns($restaurant): array
    {
        $root = $restaurant->settings['qr_sticker'] ?? [];
        $defaults = $this->defaultStickerSettings($restaurant);
        $restaurantLogoPath = $restaurant->logo_path;
        $restaurantLogoUrl = $restaurantLogoPath
            ? (\Illuminate\Support\Str::startsWith($restaurantLogoPath, ['http://', 'https://', 'uploads/'])
                ? (str_starts_with($restaurantLogoPath, 'uploads/') ? asset($restaurantLogoPath) : $restaurantLogoPath)
                : asset('storage/'.$restaurantLogoPath))
            : null;
        $designs = [];
        foreach (['table', 'room'] as $type) {
            $designs[$type] = array_merge($defaults, $root, $root['designs'][$type] ?? []);
            unset($designs[$type]['designs']);
            $designs[$type]['table_scan_text'] = $designs[$type]['table_scan_text'] ?? $defaults['table_scan_text'];
            $designs[$type]['room_scan_text'] = $designs[$type]['room_scan_text'] ?? $defaults['room_scan_text'];
            $designs[$type]['scan_text'] = $designs[$type][$type.'_scan_text'];
            $logoPath = $designs[$type]['qr_logo_path'] ?? $restaurant->logo_path;
            $designs[$type]['logo_url'] = $logoPath
                ? (\Illuminate\Support\Str::startsWith($logoPath, ['http://', 'https://', 'uploads/'])
                    ? (str_starts_with($logoPath, 'uploads/') ? asset($logoPath) : $logoPath)
                    : asset('storage/'.$logoPath))
                : null;
            $designs[$type]['restaurant_logo_url'] = $restaurantLogoUrl;
        }

        return $designs;
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
