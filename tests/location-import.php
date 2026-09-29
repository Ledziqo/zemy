<?php

// php -d extension=pdo_sqlite tests/location-import.php
// Uses a private in-memory SQLite database; never touches the configured database.
require __DIR__.'/../vendor/autoload.php';
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
putenv('APP_ENV=testing');
putenv('SESSION_DRIVER=array');
putenv('CACHE_STORE=array');
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'location_import_test', 'database.connections.location_import_test' => [
    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
]]);
(require __DIR__.'/../database/migrations/2026_06_01_000000_create_zemtab_tables.php')->up();
Illuminate\Support\Facades\Schema::table('restaurant_tables', function ($table) { $table->string('location_type')->nullable(); });
$restaurant = App\Models\Restaurant::create(['name' => 'Import fixture', 'slug' => 'import-fixture']);
$existing = App\Models\RestaurantTable::create([
    'restaurant_id' => $restaurant->id,
    'table_number' => '007',
    'location_type' => 'room',
    'table_name' => 'Old label',
    'qr_code_path' => 'qr/existing.svg',
    'is_active' => true,
]);
$importer = app(App\Support\LocationPackageImporter::class);
$csv = Illuminate\Http\UploadedFile::fake()->createWithContent('locations.csv', "number,type,name,is_active\n007,room,Room 007,1\nT01,table,Restaurant Table 1,yes\n");
$first = $importer->import($restaurant, $csv);
if ($first !== ['added' => 1, 'updated' => 1, 'total' => 2]) {
    throw new RuntimeException('First import summary was incorrect.');
}
$existing->refresh();
if ($existing->table_name !== 'Room 007' || $existing->qr_code_path !== 'qr/existing.svg' || App\Models\RestaurantTable::count() !== 2) {
    throw new RuntimeException('Existing location was not updated safely.');
}
$second = $importer->import($restaurant, Illuminate\Http\UploadedFile::fake()->createWithContent('locations.csv', "number,type,name,is_active\n007,room,Room 007,1\n"));
if ($second !== ['added' => 0, 'updated' => 1, 'total' => 1] || App\Models\RestaurantTable::count() !== 2) {
    throw new RuntimeException('Re-import was not idempotent or removed an omitted location.');
}

try {
    $importer->import($restaurant, Illuminate\Http\UploadedFile::fake()->createWithContent('bad.csv', "number,type,name,is_active\n01,room,Room 1,1\n01,table,Dup,1\n"));
    throw new RuntimeException('Duplicate CSV locations were accepted.');
} catch (RuntimeException $exception) {
    if (! str_contains($exception->getMessage(), 'appears more than once')) {
        throw $exception;
    }
}

echo "Location CSV import checks passed: adds, updates, preserves QR IDs, leaves omitted rows, and rejects duplicates.\n";
