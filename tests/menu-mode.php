<?php

// Standalone checks using in-memory models; never connects to the venue database.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Restaurant;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\RestaurantTable;
use App\Http\Middleware\EnsureRestaurantDashboardAccess;
use App\Http\Middleware\EnsureStaffProfileSelected;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

$checks = 0;
$check = function (bool $condition, string $message) use (&$checks) {
    if (! $condition) throw new RuntimeException($message);
    $checks++;
};
config(['session.driver' => 'array']);
$session = $app->make('session')->driver();
$session->start();
$app->instance('session.store', $session);
$request = Request::create('/r/test/menu');
$request->setLaravelSession($session);
$app->instance('request', $request);
view()->share('errors', new Illuminate\Support\ViewErrorBag());

$restaurant = new Restaurant(['name' => 'Test venue', 'slug' => 'test', 'business_type' => 'hotel',
    'primary_color' => '#D22630', 'settings' => []]);
$restaurant->id = 777;
$category = new Category(['name' => 'Food', 'is_active' => true]);
$category->id = 8;
$item = new MenuItem(['name' => 'Soup', 'description' => 'Fresh soup', 'price' => 125.5,
    'image_path' => 'uploads/menu-items/test-photo.jpg', 'is_available' => true]);
$item->id = 9;
$category->setRelation('menuItems', new Collection([$item]));
$restaurant->setRelation('categories', new Collection([$category]));
$table = new RestaurantTable(['table_number' => '204', 'location_type' => 'room']);
$table->setRelation('restaurant', $restaurant);
$check(! $restaurant->isMenuOnly() && $restaurant->menuPhotosEnabled(), 'Existing accounts retain full ordering and photos by default');

foreach (['restaurant', 'hotel', 'both'] as $businessType) {
    $restaurant->business_type = $businessType;
    foreach (['full', 'menu_only'] as $mode) foreach ([true, false] as $photos) {
        $restaurant->settings = ['service_mode' => $mode, 'menu_photos_enabled' => $photos];
        $html = view($mode === 'menu_only' ? 'menu.catalog' : 'menu.show', [
            'restaurant' => $restaurant, 'table' => $table, 'categories' => $restaurant->categories,
        ])->render();
        if ($businessType === 'hotel' && in_array('--preview', $argv, true)) {
            $directory = __DIR__.'/../tmp/menu-mode-preview';
            if (! is_dir($directory)) mkdir($directory, 0777, true);
            file_put_contents($directory.'/'.$mode.'-'.($photos ? 'photos' : 'text').'.html', $html);
        }
        $check(str_contains($html, 'Soup') && str_contains($html, 'ETB'), 'Menu names and prices render in every combination');
        $check(str_contains($html, 'test-photo.jpg') === $photos, 'Photo preference governs rendered item images');
        $check(str_contains($html, 'Place order') === ($mode === 'full'), 'Only full-service menus render ordering');
        $check(str_contains($html, '/service-requests') === ($mode === 'full'), 'Menu-only pages have no guest service actions');
    }
}

// Verify the shared menu does not require a room/table.
$check(str_contains(view('menu.catalog', ['restaurant' => $restaurant, 'table' => null,
    'categories' => $restaurant->categories])->render(), '/r/test/menu'), 'Shared catalog has a stable canonical URL');
$method = new ReflectionMethod(App\Http\Controllers\Admin\RestaurantController::class, 'withMenuSettings');
$restaurant->settings = ['service_mode' => 'full', 'menu_photos_enabled' => true,
    'qr_sticker' => ['accent_color' => '#D22630'], 'cbe_account_number' => 'keep'];
$data = $method->invoke(new App\Http\Controllers\Admin\RestaurantController(),
    ['service_mode' => 'menu_only', 'menu_photos_enabled' => '0'], $restaurant);
$check($data['settings']['service_mode'] === 'menu_only' && $data['settings']['menu_photos_enabled'] === false, 'Admin preferences persist independently');
$check($data['settings']['cbe_account_number'] === 'keep' && $data['settings']['qr_sticker']['accent_color'] === '#D22630', 'Mode changes preserve payment and QR settings');
$check(! isset($data['service_mode'], $data['menu_photos_enabled']), 'Admin preferences use the existing settings field');
$restaurant->settings = $data['settings'];
$options = view('admin.restaurants.menu-options', ['account' => $restaurant])->render();
$check((bool) preg_match('/value="menu_only"\s+selected/', $options) && (bool) preg_match('/value="0"\s+selected/', $options), 'Edit account renders saved mode and photo choices');

$profileVenue = new class extends Restaurant {
    public object $profileQuery;
    public App\Models\User $owner;
    public function staffProfiles() { return $this->profileQuery; }
    public function users() { return new class($this->owner) {
        public function __construct(public App\Models\User $owner) {}
        public function orderByRaw(...$args): self { return $this; }
        public function orderBy(...$args): self { return $this; }
        public function first(): App\Models\User { return $this->owner; }
    }; }
};
$profileVenue->owner = new App\Models\User(['password' => 'latest-owner-password']);
$profileVenue->profileQuery = new class {
    public bool $exists = false;
    public array $created = [];
    public function where(...$args): self { return $this; }
    public function exists(): bool { return $this->exists; }
    public function create(array $data): App\Models\StaffProfile {
        $profile = new App\Models\StaffProfile($data);
        $this->created[] = $profile;
        $this->exists = true;
        return $profile;
    }
};
$ensureProfile = new ReflectionMethod(App\Http\Controllers\Admin\RestaurantController::class, 'ensureOwnerProfile');
$ensureProfile->invoke(new App\Http\Controllers\Admin\RestaurantController(), $profileVenue);
$check(password_verify('latest-owner-password', $profileVenue->profileQuery->created[0]->password), 'Upgrade creates owner profile using current account password without rehashing the hash');
$ensureProfile->invoke(new App\Http\Controllers\Admin\RestaurantController(), $profileVenue);
$check(count($profileVenue->profileQuery->created) === 1, 'Repeated upgrades preserve existing active owner profiles');

$venue = new class {
    public bool $menuOnly = true;
    public bool $is_active = true;
    public string $dashboard_access_status = 'active';
    public function isMenuOnly(): bool { return $this->menuOnly; }
    public function subscriptions(): object {
        return new class {
            public function latest(...$args): self { return $this; }
            public function first(): mixed { return null; }
        };
    }
};
$request->setUserResolver(fn () => (object) ['role' => 'restaurant_owner', 'name' => 'Owner', 'restaurant' => $venue]);
foreach (['restaurant.menu-items.index', 'restaurant.categories.store', 'restaurant.tables.menu-qr',
    'restaurant.settings.update', 'restaurant.analytics', 'restaurant.orders.index',
    'restaurant.orders.manual.store', 'restaurant.staff-profiles.index', 'restaurant.delivery.store',
    'restaurant.cashier-reports', 'restaurant.service-requests.store', 'restaurant.future-management',
    'restaurant.tables.index', 'restaurant.tables.store', 'restaurant.tables.qr',
    'restaurant.tables.design', 'restaurant.tables.setup-pack'] as $name) {
    $route = new Route('GET', 'test', fn () => null);
    $route->name($name);
    $request->setRouteResolver(fn () => $route);
    try {
        $status = (new EnsureRestaurantDashboardAccess())->handle($request, fn () => new Response('', 204))->getStatusCode();
    } catch (HttpException $exception) { $status = $exception->getStatusCode(); }
    $expected = in_array($name, ['restaurant.menu-items.index', 'restaurant.categories.store',
        'restaurant.tables.menu-qr', 'restaurant.tables.index', 'restaurant.settings.update']) ? 204 : 403;
    $check($status === $expected, 'Menu-only access restriction: '.$name);
}
$route = new Route('GET', 'test', fn () => null);
$route->name('restaurant.dashboard');
$request->setRouteResolver(fn () => $route);
$response = (new EnsureRestaurantDashboardAccess())->handle($request, fn () => new Response('', 204));
$check($response->isRedirect(route('restaurant.menu-items.index')), 'Menu-only dashboard redirects to menu management');
$check((new EnsureStaffProfileSelected())->handle($request, fn () => new Response('', 204))->getStatusCode() === 204,
    'Menu-only owner can manage content without a workboard profile');
$venue->menuOnly = false;
$check((new EnsureRestaurantDashboardAccess())->handle($request, fn () => new Response('', 204))->getStatusCode() === 204,
    'Upgrade restores dashboard access on the next request');

// A mode flip invalidates the guest cache and an already-open signed workboard poll.
$restaurant->settings = ['service_mode' => 'menu_only'];
App\Support\PublicMenuCache::bump($restaurant);
$check(App\Support\PublicMenuCache::isMenuOnly(777), 'Downgrade immediately blocks file-backed polling');
try {
    (new App\Http\Controllers\Restaurant\DashboardController())->poll($request, 777);
    throw new RuntimeException('Menu-only workboard poll was allowed');
} catch (HttpException $exception) { $check($exception->getStatusCode() === 403, 'Previously signed poll cannot bypass menu-only mode'); }
$restaurant->settings = ['service_mode' => 'full'];
App\Support\PublicMenuCache::bump($restaurant);
$check(! App\Support\PublicMenuCache::isMenuOnly(777), 'Upgrade restores polling capability without stale mode data');
Illuminate\Support\Facades\Cache::store('file')->forget(App\Support\PublicMenuCache::modeKey(777));

// Substitute only the database transport. Guest controllers still execute real query builders
// and must deny old/crafted URLs before loading a table or creating a guest visit.
Illuminate\Support\Facades\DB::extend('menu_mode_fixture', fn () => new class(null, 'test') extends Illuminate\Database\MySqlConnection {
    public function select($query, $bindings = [], $useReadPdo = true) {
        if (! str_contains($query, '`restaurants`')) throw new RuntimeException('Menu-only endpoint performed a forbidden query: '.$query);
        return [['id' => 777, 'name' => 'Test', 'slug' => 'test', 'is_active' => 1,
            'settings' => json_encode(['service_mode' => 'menu_only'])]];
    }
});
config(['database.default' => 'menu_mode_fixture', 'database.connections.menu_mode_fixture' => ['driver' => 'menu_mode_fixture']]);
$visits = new App\Support\GuestVisitManager();
foreach ([
    fn () => (new App\Http\Controllers\OrderController())->store($request, $visits, 'test', '204'),
    fn () => (new App\Http\Controllers\OrderController())->cancel($request, $visits, 'test', '204', new App\Models\Order()),
    fn () => (new App\Http\Controllers\ServiceRequestController())->store($request, $visits, 'test', '204'),
] as $action) {
    try { $action(); throw new RuntimeException('Menu-only guest action was accepted'); }
    catch (HttpException $exception) { $check($exception->getStatusCode() === 403, 'Menu-only guest endpoint rejects crafted requests before data writes'); }
}

$request->setUserResolver(fn () => (object) ['restaurant' => $restaurant]);
$restaurant->settings = ['service_mode' => 'menu_only'];
$check((new App\Http\Controllers\Restaurant\TableController())->index($request)->name() === 'restaurant.tables.menu-only',
    'Menu-only QR page uses the single venue code without loading table or room tools');
$qr = (new App\Http\Controllers\Restaurant\TableController())->menuQr($request);
$check($qr->headers->get('Content-Type') === 'image/svg+xml' && simplexml_load_string($qr->getContent()) !== false,
    'Shared venue QR downloads as a real SVG without tables or rooms');

echo "Passed {$checks} menu-mode checks: all venue types and appearance combinations, admin setting preservation, owner access, mode changes, and workboard restrictions.\n";
