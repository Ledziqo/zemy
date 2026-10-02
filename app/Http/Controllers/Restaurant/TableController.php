<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Support\ImageOptimizer;
use App\Support\PublicMenuCache;
use App\Support\PublicSitemapCache;
use App\Support\QrSetupPackStore;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
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
                'qr' => $previewTable ? 'data:image/svg+xml;base64,'.base64_encode($this->buildQr($restaurant, $previewTable)) : null,
                'label' => $previewTable?->displayLabel() ?? ($type === 'room' ? 'Room 204' : 'Table 1'),
            ];
        }
        $designType = in_array(old('design_type', 'table'), ['table', 'room'], true) ? old('design_type', 'table') : 'table';
        $designOrientation = old('orientation', $designs[$designType]['preferred_orientation']);
        $designOrientation = in_array($designOrientation, ['portrait', 'landscape'], true) ? $designOrientation : $designs[$designType]['preferred_orientation'];
        $sticker = $designs[$designType]['orientations'][$designOrientation];
        return view('restaurant.tables.index', [
            'restaurant' => $restaurant,
            'tables' => $tables,
            'sticker' => $sticker,
            'previewCards' => $previewCards,
            'qrCardDesigns' => $designs,
            'designType' => $designType,
            'designOrientation' => $designOrientation,
        ]);
    }

    public function saveDesign(Request $request)
    {
        $rules = [];
        foreach (['background_color', 'border_color', 'text_color', 'accent_color', 'qr_color', 'qr_background_color'] as $key) {
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
            $rules['elements.'.$element] = ['sometimes', 'array:x,y,sx,sy,r'];
            foreach (['x','y','sx','sy','r'] as $dimension) {
                $rules['elements.'.$element.'.'.$dimension] = ['sometimes', 'numeric', in_array($dimension, ['sx','sy'], true) ? 'between:0.02,20' : ($dimension === 'r' ? 'between:-360,360' : 'between:-1000,1000')];
            }
        }
        $rules['qr_logo'] = ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:4096'];
        $rules['remove_qr_logo'] = ['nullable', 'boolean'];
        $rules['apply_colors_to_all'] = ['nullable', 'boolean'];
        $data = $request->validate($rules);
        $data['qr_background_color'] = $data['background_color'];
        // These controls are request-only; never store the UploadedFile or the
        // checkbox itself inside the JSON settings column.
        $applyColorsToAll = $request->boolean('apply_colors_to_all');
        unset($data['qr_logo'], $data['remove_qr_logo'], $data['apply_colors_to_all']);
        $restaurant = $this->restaurant($request);
        $settings = $restaurant->settings ?? [];
        $type = $data['design_type'];
        unset($data['design_type']);
        $orientation = $data['orientation'];
        $scanText = $data['scan_text'];
        unset($data['scan_text']);
        $qrSticker = $settings['qr_sticker'] ?? [];
        $designs = $this->qrCardDesigns($restaurant);
        $current = $designs[$type]['orientations'][$orientation];
        unset($current['scan_text'], $current['logo_url'], $current['restaurant_logo_url']);
        $data[$type.'_scan_text'] = $scanText;
        if ($request->hasFile('qr_logo')) {
            $data['qr_logo_path'] = ImageOptimizer::storeUpload($request->file('qr_logo'), 'restaurants/qr-logos', 2000, 95);
        } elseif ($request->boolean('remove_qr_logo')) {
            $data['qr_logo_path'] = null;
        }
        // Keep the QR itself high contrast regardless of the decorative palette.
        $typeDesign = $qrSticker['designs'][$type] ?? [];
        $orientationDesigns = $typeDesign['orientations'] ?? [];
        if (! array_key_exists('orientations', $typeDesign)) {
            $legacyOrientation = in_array($typeDesign['orientation'] ?? null, ['portrait', 'landscape'], true)
                ? $typeDesign['orientation']
                : ($qrSticker['orientation'] ?? 'portrait');
            if ($typeDesign !== []) {
                $orientationDesigns[$legacyOrientation] = array_merge($current, $typeDesign);
            }
        }
        $orientationDesigns[$orientation] = array_merge($current, $orientationDesigns[$orientation] ?? [], $data);
        if ($applyColorsToAll) {
            $colorKeys = ['background_color', 'text_color', 'accent_color', 'border_color', 'qr_color', 'qr_background_color'];
            $colors = array_intersect_key($data, array_flip($colorKeys));
            foreach (['table', 'room'] as $allType) {
                $allDesign = $qrSticker['designs'][$allType] ?? [];
                $allOrientations = $allDesign['orientations'] ?? [];
                foreach (['portrait', 'landscape'] as $allOrientation) {
                    $allOrientations[$allOrientation] = array_merge(
                        $this->qrCardDesigns($restaurant)[$allType]['orientations'][$allOrientation],
                        $allOrientations[$allOrientation] ?? [],
                        $colors,
                    );
                }
                $qrSticker['designs'][$allType] = [
                    'preferred_orientation' => $allDesign['preferred_orientation'] ?? $allOrientation,
                    'orientations' => $allOrientations,
                ];
            }
        }
        $qrSticker['designs'][$type] = [
            'preferred_orientation' => $orientation,
            'orientations' => $orientationDesigns,
        ];
        $settings['qr_sticker'] = $qrSticker;
        $restaurant->update(['settings' => $settings]);
        QrSetupPackStore::invalidate((int) $restaurant->id);
        return redirect()->route('restaurant.tables.index', [], 303)->with('success', 'QR design saved. Open the setup pack to print your updated cards.');
    }

    public function setupPack(Request $request)
    {
        $restaurant = $this->restaurant($request);
        if ($url = QrSetupPackStore::currentUrl((int) $restaurant->id)) {
            return redirect()->away($url);
        }

        $designs = $this->qrCardDesigns($restaurant);
        $allTables = $restaurant->tables()->orderByRaw('CAST(table_number AS UNSIGNED)')->get();
        $allTables->each(fn (RestaurantTable $table) => $table->setRelation('restaurant', $restaurant));
        $tables = $allTables->filter(fn (RestaurantTable $table) => $table->is_active)->values();
        $tableCount = $tables->count();
        $roomCount = $tables->filter(fn (RestaurantTable $table) => $table->isRoomServicePoint())->count();
        $tablePageCount = (int) ceil(($tableCount - $roomCount) / 8);
        $roomPageCount = (int) ceil($roomCount / 3);
        $pageCount = $tablePageCount + $roomPageCount;
        $buildToken = QrSetupPackStore::begin($restaurant, $allTables, $pageCount);
        $batchUrls = [];
        for ($page = 0; $page < $pageCount; $page++) {
            $batchUrls[] = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'qr.setup-pack.batch',
                now()->addHours(3),
                ['restaurantId' => $restaurant->id, 'token' => $buildToken, 'page' => $page],
            );
        }

        return view('restaurant.tables.setup_pack', [
            'restaurant' => $restaurant,
            'sticker' => $designs['table']['orientations'][$designs['table']['preferred_orientation']],
            'tableCount' => $tableCount,
            'pageCount' => $pageCount,
            'roomCount' => $roomCount,
            'tableOnlyCount' => $tableCount - $roomCount,
            'batchSize' => 8,
            'buildToken' => $buildToken,
            'batchUrls' => $batchUrls,
        ]);
    }

    public function setupPackBatch(int $restaurantId, string $token, int $page)
    {
        $html = QrSetupPackStore::renderPageOnce($restaurantId, $token, $page, function () use ($restaurantId, $token, $page) {
            $snapshot = QrSetupPackStore::snapshot($restaurantId, $token);
            abort_unless($snapshot !== null, 404);

            $restaurant = (new Restaurant())->newFromBuilder($snapshot['restaurant']);
            $allTables = collect(array_map(
                fn (array $attributes) => (new RestaurantTable())->newFromBuilder($attributes),
                $snapshot['tables'],
            ));
            $allTables->each(fn (RestaurantTable $table) => $table->setRelation('restaurant', $restaurant));
            $tableCards = $allTables->filter(fn (RestaurantTable $table) => ! $table->isRoomServicePoint())->values();
            $roomCards = $allTables->filter(fn (RestaurantTable $table) => $table->isRoomServicePoint())->values();
            $tablePageCount = (int) ceil($tableCards->count() / 8);
            $type = $page < $tablePageCount ? 'table' : 'room';
            $typePage = $type === 'table' ? $page : $page - $tablePageCount;
            $cardsPerPage = $type === 'table' ? 8 : 3;
            $tables = ($type === 'table' ? $tableCards : $roomCards)->slice($typePage * $cardsPerPage, $cardsPerPage)->values();
            $designs = $this->qrCardDesigns($restaurant);
            // Setup packs use a consistent print orientation by location type:
            // tables/lobby in portrait, guest rooms in landscape. Keep each
            // type's saved editor variants untouched.
            $orientation = $type === 'room' ? 'landscape' : 'portrait';
            $sticker = $designs[$type]['orientations'][$orientation];
            $qrImages = $tables->mapWithKeys(fn (RestaurantTable $table) => [
                $table->id => 'data:image/svg+xml;base64,'.base64_encode($this->cachedQrSvg($restaurant, $table, $sticker)),
            ]);

            return view('restaurant.tables.setup_pack_batch', compact('restaurant', 'tables', 'qrImages', 'sticker', 'type'))->render();
        });
        abort_unless($html !== null, 404);

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

    private function cachedQrSvg($restaurant, RestaurantTable $table, ?array $sticker = null): string
    {
        $type = $table->isRoomServicePoint() ? 'room' : 'table';
        $sticker ??= $this->qrCardDesigns($restaurant)[$type]['orientations'][$type === 'room' ? 'landscape' : 'portrait'];
        $signature = sha1(route('menu.show', [$restaurant->slug, $table->table_number]).'|'.$sticker['qr_color'].'|'.$sticker['qr_background_color'].'|zemtab-center-mark-v2');
        $relativePath = 'uploads/qr-codes/'.$restaurant->id.'/'.$table->id.'-'.$signature.'.svg';
        $absolutePath = public_path($relativePath);

        if (is_file($absolutePath)) {
            $svg = @file_get_contents($absolutePath);
            if ($svg !== false) {
                return $svg;
            }
        }

        $svg = $this->buildQr($restaurant, $table, $sticker);
        $directory = dirname($absolutePath);
        if (! is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }
        if (is_dir($directory) && @file_put_contents($absolutePath, $svg, LOCK_EX) !== false) {
        }

        return $svg;
    }

    private function buildQr($restaurant, RestaurantTable $table, ?array $sticker = null)
    {
        $type = $table->isRoomServicePoint() ? 'room' : 'table';
        $sticker ??= $this->qrCardDesigns($restaurant)[$type]['orientations'][$type === 'room' ? 'landscape' : 'portrait'];

        $result = (new Builder(
            writer: new SvgWriter(),
            data: route('menu.show', [$restaurant->slug, $table->table_number]),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 500,
            margin: 20,
            foregroundColor: $this->qrColor($sticker['qr_color']),
            backgroundColor: $this->qrColor($sticker['qr_background_color']),
        ))->build();

        // Embed a small ZemTab mark into the real QR artwork so
        // it appears in downloads, editor previews, and printed setup packs.
        // High error correction and the modest central knockout preserve scanability.
        $svg = simplexml_load_string($result->getString());
        abort_unless($svg instanceof \SimpleXMLElement, 500, 'Could not prepare branded QR code.');
        $logoPath = public_path('logo/zemtab-pantone-1795-c-icon-transparent.png');
        $logoBytes = @file_get_contents($logoPath);
        abort_unless(is_string($logoBytes), 500, 'The ZemTab QR logo is unavailable.');

        $size = (float) $result->getMatrix()->getOuterSize();
        $logoHeight = 80.0;
        $logoWidth = 348.0 / 453.0 * $logoHeight;
        $backingWidth = $logoWidth + 22.0;
        $backingHeight = $logoHeight + 22.0;
        $center = $size / 2;

        $backing = $svg->addChild('rect', null, 'http://www.w3.org/2000/svg');
        $backing->addAttribute('x', (string) ($center - $backingWidth / 2));
        $backing->addAttribute('y', (string) ($center - $backingHeight / 2));
        $backing->addAttribute('width', (string) $backingWidth);
        $backing->addAttribute('height', (string) $backingHeight);
        $backing->addAttribute('rx', '8');
        $backing->addAttribute('fill', $sticker['qr_background_color']);

        $logo = $svg->addChild('image', null, 'http://www.w3.org/2000/svg');
        $logo->addAttribute('x', (string) ($center - $logoWidth / 2));
        $logo->addAttribute('y', (string) ($center - $logoHeight / 2));
        $logo->addAttribute('width', (string) $logoWidth);
        $logo->addAttribute('height', (string) $logoHeight);
        $logo->addAttribute('preserveAspectRatio', 'xMidYMid meet');
        $logo->addAttribute('href', 'data:image/png;base64,'.base64_encode($logoBytes));

        return $svg->asXML();
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
            'background_color' => '#F4F1EC',
            'border_color' => '#C8C2AE',
            'text_color' => '#111111',
            'accent_color' => '#000000',
            'qr_color' => '#111111',
            'qr_background_color' => '#F4F1EC',
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
            'qr_size' => 50,
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
        $storedDesigns = $root['designs'] ?? [];
        unset($root['designs']);
        $base = array_merge($defaults, $root);
        $restaurantLogoPath = $restaurant->logo_path;
        $restaurantLogoUrl = $restaurantLogoPath
            ? (\Illuminate\Support\Str::startsWith($restaurantLogoPath, ['http://', 'https://', 'uploads/'])
                ? (str_starts_with($restaurantLogoPath, 'uploads/') ? asset($restaurantLogoPath) : $restaurantLogoPath)
                : asset('storage/'.$restaurantLogoPath))
            : null;
        $designs = [];
        foreach (['table', 'room'] as $type) {
            $typeDesign = $storedDesigns[$type] ?? [];
            $orientationDesigns = $typeDesign['orientations'] ?? [];
            if (! array_key_exists('orientations', $typeDesign)) {
                $legacyOrientation = in_array($typeDesign['orientation'] ?? null, ['portrait', 'landscape'], true)
                    ? $typeDesign['orientation']
                    : ($base['orientation'] ?? 'portrait');
                if ($typeDesign !== []) {
                    $orientationDesigns[$legacyOrientation] = $typeDesign;
                }
            }
            $defaultPreferred = $type === 'room' ? 'landscape' : 'portrait';
            $preferred = $typeDesign['preferred_orientation'] ?? ($typeDesign['orientation'] ?? ($base['orientation'] ?? $defaultPreferred));
            if ($typeDesign === [] && $root === []) {
                $preferred = $defaultPreferred;
            }
            if (! in_array($preferred, ['portrait', 'landscape'], true)) {
                $preferred = 'portrait';
            }
            $designs[$type] = ['preferred_orientation' => $preferred, 'orientations' => []];
            foreach (['portrait', 'landscape'] as $orientation) {
                $orientationDefaults = $orientation === 'landscape' ? ['qr_size' => 46] : ['qr_size' => 50];
                $design = array_merge($base, $orientationDefaults, $orientationDesigns[$orientation] ?? []);
                $design['orientation'] = $orientation;
                $design['qr_background_color'] = $design['background_color'];
                $design['table_scan_text'] = $design['table_scan_text'] ?? $defaults['table_scan_text'];
                $design['room_scan_text'] = $design['room_scan_text'] ?? $defaults['room_scan_text'];
                $design['scan_text'] = $design[$type.'_scan_text'];
                $storedOrientation = $orientationDesigns[$orientation] ?? [];
                $hasExplicitQrLogo = array_key_exists('qr_logo_path', $storedOrientation)
                    || array_key_exists('qr_logo_path', $typeDesign)
                    || array_key_exists('qr_logo_path', $root);
                $logoPath = $hasExplicitQrLogo
                    ? $design['qr_logo_path']
                    : (($typeDesign !== [] || $root !== []) ? $restaurant->logo_path : null);
                $design['logo_url'] = $logoPath
                    ? (\Illuminate\Support\Str::startsWith($logoPath, ['http://', 'https://', 'uploads/'])
                        ? (str_starts_with($logoPath, 'uploads/') ? asset($logoPath) : $logoPath)
                        : asset('storage/'.$logoPath))
                    : null;
                $design['restaurant_logo_url'] = $restaurantLogoUrl;
                $designs[$type]['orientations'][$orientation] = $design;
            }
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
