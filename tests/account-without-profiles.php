<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array']);
$session = app('session')->driver();
$session->start();
$restaurant = new class extends App\Models\Restaurant {
    public bool $profilesExist = false;
    public function staffProfiles() { return new class($this->profilesExist) {
        public function __construct(public bool $exists) {}
        public function exists(): bool { return $this->exists; }
    }; }
};
$restaurant->settings = ['service_mode' => 'full'];
$user = new App\Models\User(['role' => 'restaurant_owner', 'name' => 'Owner']);
$user->setRelation('restaurant', $restaurant);
$request = Illuminate\Http\Request::create('/restaurant/dashboard', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
$request->setLaravelSession($session);
app()->instance('request', $request);
$request->setUserResolver(fn () => $user);
$checks = 0;
$check = function (bool $condition, string $label) use (&$checks) { if (! $condition) throw new RuntimeException($label); $checks++; };
$middleware = new App\Http\Middleware\EnsureStaffProfileSelected();
$run = function (string $name) use ($request, $middleware) {
    $route = new Illuminate\Routing\Route('GET', '/test', fn () => null);
    $route->name($name);
    $request->setRouteResolver(fn () => $route);
    try { return $middleware->handle($request, fn () => new Symfony\Component\HttpFoundation\Response('', 204))->getStatusCode(); }
    catch (Symfony\Component\HttpKernel\Exception\HttpException $exception) { return $exception->getStatusCode(); }
};
$session->put('staff_profile_id', 999);
foreach (['restaurant.dashboard', 'restaurant.orders.index', 'restaurant.analytics', 'restaurant.menu-items.index', 'restaurant.settings.update', 'restaurant.staff-profiles.index'] as $route) {
    $status = $run($route);
    $check($status === 204, 'Owner without profiles can access '.$route.' (status '.$status.', role '.$session->get('staff_profile_role').')');
}
$check(! $session->has('staff_profile_id') && $session->get('staff_profile_role') === 'owner_manager', 'Account login clears stale profile IDs and sets owner permissions');
$check($session->get('account_login_without_profile') === true, 'Navigation knows there is no profile to switch');
$response = (new App\Http\Controllers\AuthController())->showProfileSelect($request);
$check($response->isRedirect(route('restaurant.dashboard')), 'Old profile-selection links redirect straight to dashboard');
$restaurant->profilesExist = true;
$check($run('restaurant.dashboard') === 401, 'Creating profiles restores mandatory profile login on the next request');
$check(! $session->has('staff_profile_role'), 'Stale owner permissions are cleared when profiles become required');
$check($restaurant->requiresStaffProfile($user), 'Existing profiles require authentication even if all are disabled');
$restaurant->profilesExist = false;
$user->role = 'staff';
$check($run('restaurant.orders.index') === 204, 'Staff account without profiles can use cashier workboard');
$check($run('restaurant.analytics') === 403, 'Staff account fallback does not grant owner privileges');
$user->role = 'restaurant_owner';
$restaurant->settings = ['service_mode' => 'menu_only'];
$check($run('restaurant.menu-items.index') === 204, 'Menu-only owner remains able to manage menu without profiles');
echo "Passed {$checks} profile-free account checks.\n";
