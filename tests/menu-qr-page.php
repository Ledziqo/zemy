<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array']);
$session = app('session')->driver();
$session->start();
$restaurant = new class extends App\Models\Restaurant {
    public function latestSubscription() { return null; }
};
$restaurant->fill(['name' => 'Nazra', 'slug' => 'nazra', 'business_type' => 'hotel',
    'settings' => ['service_mode' => 'menu_only', 'menu_photos_enabled' => false]]);
$user = new App\Models\User(['role' => 'restaurant_owner', 'name' => 'Nazra Owner']);
$user->setRelation('restaurant', $restaurant);
$request = Illuminate\Http\Request::create('/restaurant/tables');
$request->setLaravelSession($session);
app()->instance('request', $request);
Illuminate\Support\Facades\Auth::setUser($user);
$request->setUserResolver(fn () => $user);
$session->put('staff_profile_role', 'owner_manager');
view()->share('errors', new Illuminate\Support\ViewErrorBag());
$originalRoutes = app('router')->getRoutes();
$request->setRouteResolver(fn () => $originalRoutes->getByName('restaurant.tables.index'));
$checks = 0;
$check = function (bool $value, string $message) use (&$checks) { if (! $value) throw new RuntimeException($message); $checks++; };
foreach (['current', 'previous-cache'] as $version) {
    if ($version === 'previous-cache') {
        $routes = new Illuminate\Routing\RouteCollection();
        foreach ($originalRoutes as $route) {
            if (! in_array($route->getName(), ['menu.catalog', 'restaurant.tables.menu-qr'])) $routes->add($route);
        }
        app('router')->setRoutes($routes);
        app('url')->setRoutes($routes);
    }
    $controller = new App\Http\Controllers\Restaurant\TableController();
    $html = $controller->index($request)->render();
    $check(str_contains($html, 'data:image/svg+xml;base64,'), $version.': whole page renders with embedded QR');
    $check(str_contains($html, 'download=menu-qr'), $version.': download uses existing route');
    $check(! str_contains($html, 'Select your profile'), $version.': no profile selection required');
    $request->query->set('download', 'menu-qr');
    $response = $controller->index($request);
    $check($response->headers->get('Content-Type') === 'image/svg+xml' && simplexml_load_string($response->getContent()) !== false, $version.': download returns SVG');
    $request->query->remove('download');
    $url = App\Support\PublicMenuCache::catalogUrl($restaurant);
    $check(str_contains($url, $version === 'current' ? '/r/nazra/menu' : '/r/nazra/table/__zemtab_menu__'), $version.': shared menu has a supported URL');
    $catalog = view('menu.catalog', ['restaurant' => $restaurant, 'table' => null, 'categories' => collect()])->render();
    $check(str_contains($catalog, 'Nazra'), $version.': catalog also renders with previous route cache');
}
echo "Passed {$checks} complete Menu QR page checks, including a deployment with its previous route cache.\n";
