<?php
declare(strict_types=1);

// Shared exclusively by CLI verification and the loopback preview router.
function launchApp(string $name = 'preview'): \Illuminate\Foundation\Application
{
    if (!in_array($name, ['preview', 'checks'], true)) throw new RuntimeException('Invalid fixture name');
    $root = dirname(__DIR__);
    $dir = $root.'/storage/framework/testing/launch-'.$name;
    foreach (['', '/sessions', '/views', '/cache', '/files'] as $suffix) {
        if (!is_dir($dir.$suffix)) mkdir($dir.$suffix, 0700, true);
    }
    // Never load .env or any cached production configuration/routes.
    foreach (['APP_CONFIG_CACHE', 'APP_ROUTES_CACHE', 'APP_EVENTS_CACHE'] as $key) {
        putenv($key.'='.$dir.'/unused-'.$key.'.php');
        $_ENV[$key] = $_SERVER[$key] = $dir.'/unused-'.$key.'.php';
    }
    require_once $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) use ($dir, $name) {
        $keyFile = $dir.'/key';
        if (!is_file($keyFile)) file_put_contents($keyFile, 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set([
            'app.env' => 'testing', 'app.debug' => false, 'app.key' => trim(file_get_contents($keyFile)),
            'app.url' => 'http://127.0.0.1:8091',
            'database.default' => 'sqlite',
            'database.connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => $dir.'/fixture.sqlite', 'prefix' => '', 'foreign_key_constraints' => true, 'busy_timeout' => 5000]],
            'session.driver' => 'file', 'session.files' => $dir.'/sessions', 'session.cookie' => 'zemtab_launch_'.$name,
            'session.domain' => null, 'session.secure' => false,
            'cache.default' => 'array', 'cache.stores' => ['array' => ['driver' => 'array', 'serialize' => false]],
            'view.compiled' => $dir.'/views', 'queue.default' => 'sync', 'mail.default' => 'log',
            'logging.default' => 'single', 'logging.channels.single.path' => $dir.'/laravel.log',
            'filesystems.default' => 'local', 'filesystems.disks' => ['local' => ['driver' => 'local', 'root' => $dir.'/files'], 'public' => ['driver' => 'local', 'root' => $dir.'/files', 'url' => '/storage']],
        ]);
        $app->instance('env', 'testing');
    });
    $app->bootstrapWith([
        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
        \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
        \Illuminate\Foundation\Bootstrap\BootProviders::class,
    ]);
    return $app;
}

function launchSeed(): array
{
    $db = config('database.connections.sqlite.database');
    $credentials = dirname($db).'/credentials.json';
    if (is_file($db)) return json_decode(file_get_contents($credentials), true, flags: JSON_THROW_ON_ERROR);
    touch($db);
    // Explicit allowlist: all run against an empty database before fictional data.
    $migrations = [
        '0001_01_01_000000_create_users_table', '0001_01_01_000001_create_cache_table',
        '0001_01_01_000002_create_jobs_table', '2026_06_01_000000_create_zemtab_tables',
        '2026_06_03_000000_add_dashboard_access_to_restaurants', '2026_06_05_000000_add_business_type_to_restaurants',
        '2026_06_06_000000_add_guest_sessions_and_payment_proofs', '2026_06_21_000000_add_payment_method_to_subscriptions',
        '2026_06_23_000000_add_scale_readiness_indexes_and_menu_cache_version',
        '2026_06_24_210000_create_staff_profiles_and_order_enhancements', '2026_06_25_000000_add_confirmed_at_to_orders',
        '2026_06_26_000000_add_business_type_to_demo_requests',
        '2026_07_16_000000_add_kitchen_screen_setting', '2026_07_17_000000_add_menu_image_source',
    ];
    foreach ($migrations as $migration) (require base_path('database/migrations/'.$migration.'.php'))->up();
    $password = bin2hex(random_bytes(12));
    $result = ['password' => $password, 'tenants' => []];
    foreach (['aurora', 'birch'] as $slug) {
        $r = \App\Models\Restaurant::create(['name' => ucfirst($slug).' Fictional Cafe', 'slug' => 'launch-'.$slug, 'is_active' => true, 'dashboard_access_status' => 'active', 'kitchen_screen_enabled' => true, 'settings' => ['payment_methods' => ['cash'], 'service_charge_percentage' => 10, 'vat_percentage' => 15]]);
        $u = \App\Models\User::create(['name' => 'Local '.$slug, 'email' => $slug.'@launch.test', 'password' => $password, 'role' => 'restaurant_owner', 'restaurant_id' => $r->id]);
        $profiles = [];
        foreach (['owner_manager', 'cashier', 'kitchen'] as $role) $profiles[$role] = \App\Models\StaffProfile::create(['restaurant_id' => $r->id, 'name' => ucfirst(str_replace('_', ' ', $role)), 'role' => $role, 'password' => $password, 'is_active' => true])->id;
        $c = $r->categories()->create(['name' => 'Fictional dishes']);
        $m = $r->menuItems()->create(['category_id' => $c->id, 'name' => ucfirst($slug).' lentil bowl', 'price' => 100, 'is_available' => true]);
        $t = $r->tables()->create(['table_number' => '1', 'table_name' => 'Window table', 'is_active' => true]);
        $orders = [];
        foreach (['new', 'preparing', 'served', 'paid'] as $status) {
            $o = $r->orders()->create(['table_id' => $t->id, 'table_number' => '1', 'status' => $status, 'confirmed_at' => now(), 'subtotal' => 100, 'total' => 125, 'tax' => 15, 'service_charge' => 10, 'payment_status' => $status === 'paid' ? 'paid' : 'unpaid']);
            $o->items()->create(['menu_item_id' => $m->id, 'item_name' => $m->name, 'quantity' => 1, 'unit_price' => 100, 'total_price' => 100]);
            $orders[$status] = $o->id;
        }
        $result['tenants'][$slug] = ['restaurant' => $r->id, 'user' => $u->id, 'email' => $u->email, 'profiles' => $profiles, 'item' => $m->id, 'table' => $t->id, 'orders' => $orders];
    }
    $result['admin'] = \App\Models\User::create(['name' => 'Local administrator', 'email' => 'admin@launch.test', 'password' => $password, 'role' => 'admin'])->id;
    file_put_contents($credentials, json_encode($result, JSON_PRETTY_PRINT));
    return $result;
}
