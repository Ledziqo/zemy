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

    $token = App\Support\QrSetupPackStore::begin($restaurant, $tables, 3);
    $check(App\Support\QrSetupPackStore::begin($restaurant, $tables, 3) === $token, 'concurrent opens reuse the active pack build');
    $snapshot = App\Support\QrSetupPackStore::snapshot(77, $token);
    $check(is_array($snapshot) && count($snapshot['tables']) === 17, 'build snapshot includes active tables only');

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

    App\Support\QrSetupPackStore::invalidate(77);
    $check(App\Support\QrSetupPackStore::begin($restaurant, $tables, 3) !== $token, 'invalidation prevents reusing a stale build');
} finally {
    Illuminate\Support\Facades\File::deleteDirectory($temporaryStorage);
}

echo "Passed $checks QR setup-pack storage checks; no database access.\n";
