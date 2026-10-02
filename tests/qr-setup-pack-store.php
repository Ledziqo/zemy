<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$temporaryStorage = sys_get_temp_dir().'/zemtab-qr-pack-'.bin2hex(random_bytes(8));
$app->useStoragePath($temporaryStorage);
$checks = 0;
$check = function (bool $condition, string $message) use (&$checks): void {
    if (! $condition) {
        throw new RuntimeException($message);
    }
    $checks++;
};

try {
    $restaurant = new App\Models\Restaurant;
    $restaurant->forceFill([
        'id' => 77,
        'name' => 'Pack test venue',
        'slug' => 'pack-test',
        'business_type' => 'hotel',
        'primary_color' => '#D22630',
        'logo_path' => null,
        'settings' => ['qr_sticker' => ['orientation' => 'portrait']],
        'updated_at' => '2026-09-30 00:00:00',
    ]);

    $tables = collect();
    for ($id = 1; $id <= 18; $id++) {
        $table = new App\Models\RestaurantTable;
        $table->forceFill([
            'id' => $id,
            'restaurant_id' => 77,
            'table_number' => (string) $id,
            'table_name' => null,
            'location_type' => $id % 2 === 0 ? 'room' : 'table',
            'is_active' => $id !== 18,
            'updated_at' => '2026-09-30 00:00:00',
        ]);
        $table->setRelation('restaurant', $restaurant);
        $tables->push($table);
    }

    $printOptions = [
        'table' => ['orientation' => 'landscape', 'size' => 'small'],
        'room' => ['orientation' => 'portrait', 'size' => 'medium'],
    ];
    $token = App\Support\QrSetupPackStore::begin($restaurant, $tables, 3, $printOptions);
    $check(App\Support\QrSetupPackStore::begin($restaurant, $tables, 3, $printOptions) === $token, 'identical print options reuse the active pack build');
    $snapshot = App\Support\QrSetupPackStore::snapshot(77, $token);
    $check(is_array($snapshot) && count($snapshot['tables']) === 17, 'build snapshot includes active tables only');
    $check(($snapshot['print_options'] ?? null) === $printOptions, 'chosen print orientations and sizes are captured in the build snapshot');
    $differentToken = App\Support\QrSetupPackStore::begin($restaurant, $tables, 3, [
        'table' => ['orientation' => 'portrait', 'size' => 'large'],
        'room' => ['orientation' => 'landscape', 'size' => 'large'],
    ]);
    $check($differentToken !== $token, 'different print selections cannot reuse a mismatched prepared build');

    $renders = 0;
    $first = App\Support\QrSetupPackStore::renderPageOnce(77, $token, 0, function () use (&$renders): string {
        $renders++;
        return '<section>cached page</section>';
    });
    $second = App\Support\QrSetupPackStore::renderPageOnce(77, $token, 0, function () use (&$renders): string {
        $renders++;
        return '<section>duplicate render</section>';
    });
    $check($first === '<section>cached page</section>' && $second === $first && $renders === 1, 'repeated page requests return the disk-cached page without rerendering');
    $check(App\Support\QrSetupPackStore::renderPageOnce(77, $token, 3, fn () => 'invalid') === null, 'out-of-range batch page is rejected');

    $controller = new App\Http\Controllers\Restaurant\TableController;
    $designsForPack = new ReflectionMethod($controller, 'setupPackPagePlan');
    $activeTables = $tables->filter(fn ($table) => (bool) $table->is_active)->values();
    $pagePlan = $designsForPack->invoke($controller, $activeTables, [
        'table' => ['preferred_orientation' => 'landscape', 'orientations' => ['landscape' => ['card_size' => 'large']]],
        'room' => ['preferred_orientation' => 'portrait', 'orientations' => ['portrait' => ['card_size' => 'small']]],
    ]);
    $check(count($pagePlan) === 4, 'independent saved orientations and sizes determine pack pagination');
    $check($pagePlan[0]['type'] === 'table' && $pagePlan[0]['orientation'] === 'landscape' && $pagePlan[0]['capacity'] === 3, 'landscape Large tables keep the existing three-card page capacity');
    $check($pagePlan[3]['type'] === 'room' && $pagePlan[3]['orientation'] === 'portrait' && $pagePlan[3]['size'] === 'small' && $pagePlan[3]['capacity'] === 18, 'portrait Small rooms use their own orientation and denser page capacity');

    $smallPack = $designsForPack->invoke($controller, $activeTables, [
        'table' => ['preferred_orientation' => 'portrait', 'orientations' => ['portrait' => ['card_size' => 'small']]],
        'room' => ['preferred_orientation' => 'landscape', 'orientations' => ['landscape' => ['card_size' => 'medium']]],
    ]);
    $check(count($smallPack) === 2 && $smallPack[0]['capacity'] === 18 && $smallPack[1]['capacity'] === 15, 'small and medium cards are tiled efficiently in either orientation');
    $overriddenPack = $designsForPack->invoke($controller, $activeTables, [
        'table' => ['preferred_orientation' => 'portrait', 'orientations' => ['portrait' => ['card_size' => 'large']]],
        'room' => ['preferred_orientation' => 'landscape', 'orientations' => ['landscape' => ['card_size' => 'large']]],
    ], $printOptions);
    $check($overriddenPack[0]['orientation'] === 'landscape' && $overriddenPack[0]['size'] === 'small' && $overriddenPack[0]['capacity'] === 18, 'print-dialog selections override saved editor preferences for this pack');

    $freshRestaurant = new App\Models\Restaurant;
    $freshRestaurant->forceFill(['settings' => []]);
    $cardDesigns = new ReflectionMethod($controller, 'qrCardDesigns');
    $defaults = $cardDesigns->invoke($controller, $freshRestaurant);
    $check($defaults['table']['preferred_orientation'] === 'portrait' && $defaults['room']['preferred_orientation'] === 'portrait', 'new card groups use a neutral shared orientation default');
    $check($defaults['table']['orientations']['portrait']['card_size'] === 'large', 'current card dimensions remain the default Large size');

    App\Support\QrSetupPackStore::invalidate(77);
    $check(App\Support\QrSetupPackStore::begin($restaurant, $tables, 3) !== $token, 'invalidation prevents reusing a stale build');
} finally {
    Illuminate\Support\Facades\File::deleteDirectory($temporaryStorage);
}

echo "Passed $checks QR setup-pack storage checks; no database access.\n";
