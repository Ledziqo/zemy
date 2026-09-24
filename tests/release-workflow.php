<?php

// php -d extension=pdo_sqlite tests/release-workflow.php
// Only an in-memory SQLite database; never migrate the configured application database.
require __DIR__.'/../vendor/autoload.php';
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
putenv('APP_ENV=testing');
putenv('SESSION_DRIVER=array');
putenv('CACHE_STORE=array');
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'release_test', 'database.connections.release_test' => [
    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
]]);
$schema = Illuminate\Support\Facades\Schema::connection('release_test');
(require __DIR__.'/../database/migrations/2026_06_01_000000_create_zemtab_tables.php')->up();
$schema->table('restaurants', function ($table) { $table->boolean('kitchen_screen_enabled')->default(false); });
$schema->table('restaurant_tables', function ($table) { $table->string('location_type')->nullable(); });
$schema->table('orders', function ($table) {
    $table->timestamp('confirmed_at')->nullable();
    $table->string('order_type')->default('dine_in');
    $table->unsignedBigInteger('guest_session_id')->nullable();
});
$sentinel = App\Models\Restaurant::create(['name' => 'Existing venue', 'slug' => 'existing-venue']);
$before = $sentinel->fresh()->getAttributes();
$checks = app(App\Support\ReleaseWorkflowTest::class)->run();
foreach ($checks as $check) {
    echo $check['status'].' '.$check['name'].' '.$check['detail'].PHP_EOL;
}
$failed = array_filter($checks, fn ($check) => $check['status'] !== 'PASS');
if ($failed || count($checks) !== 17 || App\Models\Restaurant::count() !== 1 || $sentinel->fresh()->getAttributes() !== $before || App\Models\Order::count() !== 0 || Illuminate\Support\Facades\DB::transactionLevel() !== 0) {
    fwrite(STDERR, "Workflow assertions or preservation/cleanup checks failed.\n");
    exit(1);
}
echo "All workflow assertions passed; existing venue unchanged; no synthetic rows remain.\n";
