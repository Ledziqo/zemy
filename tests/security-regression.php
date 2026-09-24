<?php

// Standalone: php tests/security-regression.php. No application bootstrap, .env, or database.
require __DIR__.'/../vendor/autoload.php';

use App\Http\Controllers\SetupController;
use App\Http\Middleware\EnsureStaffProfileSelected;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

// Replace only persistence; exercise real middleware, requests, sessions, and routing.
class SecurityProfileFixture
{
    public const ROLES = ['owner_manager', 'cashier', 'kitchen'];
    public static ?object $record = null;
    private array $conditions = [];

    public static function __callStatic($name, $args): self
    {
        if ($name !== 'where') {
            throw new LogicException('Unexpected persistence operation');
        }
        return (new self)->addCondition(...$args);
    }

    public function __call($name, $args)
    {
        if ($name !== 'where') {
            throw new LogicException('Unexpected persistence operation');
        }
        return $this->addCondition(...$args);
    }

    private function addCondition($key, $value): self
    {
        $this->conditions[$key] = $value;
        return $this;
    }

    public function find($id): ?object
    {
        $record = self::$record;
        foreach (['id' => $id, ...$this->conditions] as $key => $value) {
            if (! $record || $record->$key != $value) {
                return null;
            }
        }
        return $record;
    }
}
class_alias(SecurityProfileFixture::class, 'App\\Models\\StaffProfile');

$app = new Application(dirname(__DIR__));
Facade::setFacadeApplication($app);
$router = new Router(new Dispatcher($app), $app);
$app->instance('router', $router);
require __DIR__.'/../routes/web.php';
$router->getRoutes()->refreshNameLookups();

$checks = 0;
function check(bool $condition, string $label): void
{
    global $checks;
    if (! $condition) {
        throw new RuntimeException($label);
    }
    $checks++;
}

$pollRoute = $router->getRoutes()->getByName('restaurant.orders.poll');
if ($pollRoute) {
    $pollMiddleware = $pollRoute->gatherMiddleware();
    check(in_array('signed', $pollMiddleware) && in_array('throttle:300,1', $pollMiddleware), 'Work Board polling must use a signed throttled URL');
    check(! in_array('auth', $pollMiddleware), 'unchanged Work Board polls must not load the database-backed auth provider');
}
$qrPrintRoute = $router->getRoutes()->getByName('qr.print');
if ($qrPrintRoute) {
    $qrMiddleware = $qrPrintRoute->gatherMiddleware();
    check(in_array('signed', $qrMiddleware) && ! in_array('auth', $qrMiddleware), 'prepared QR print links must expire by signature and avoid DB auth');
}

foreach (['setup.show', 'setup.run', 'admin.setup.run'] as $name) {
    $middleware = $router->getRoutes()->getByName($name)->gatherMiddleware();
    check(in_array('auth', $middleware) && in_array('role:admin', $middleware), "$name requires admin");
}
$scanMiddleware = $router->getRoutes()->getByName('admin.database.full-scan')->gatherMiddleware();
check(in_array('auth', $scanMiddleware) && in_array('role:admin', $scanMiddleware), 'full release scan requires an authenticated admin');
check(in_array('throttle:1,5', $scanMiddleware), 'full release scan is rate-limited');
check(in_array('throttle:10,1', $router->getRoutes()->getByName('restaurant.profile-login')->gatherMiddleware()), 'profile login is throttled');

$restaurant = new class {
    public int $id = 7;
    public bool $kitchen = true;
    public function kitchenScreenEnabled(): bool { return $this->kitchen; }
};
$request = Request::create('/restaurant/dashboard', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
$request->setUserResolver(fn () => (object) ['restaurant' => $restaurant]);
$session = new Store('security-test', new ArraySessionHandler(120));
$request->setLaravelSession($session);
$run = function (string $name) use ($request): int {
    $request->setRouteResolver(fn () => (new Route('GET', '/', fn () => null))->name($name));
    try {
        return (new EnsureStaffProfileSelected)->handle($request, fn () => new Response('', 204))->getStatusCode();
    } catch (HttpException $e) {
        return $e->getStatusCode();
    }
};

foreach (SecurityProfileFixture::ROLES as $role) {
    SecurityProfileFixture::$record = (object) ['id' => 3, 'restaurant_id' => 7, 'is_active' => true, 'role' => $role, 'name' => 'Current name'];
    $session->put(['staff_profile_id' => 3, 'staff_profile_role' => 'owner_manager']);
    foreach ($router->getRoutes() as $route) {
        if (! str_starts_with($route->getName() ?? '', 'restaurant.') || ! in_array('profile.selected', $route->gatherMiddleware())) {
            continue;
        }
        $name = $route->getName();
        $allowed = $role === 'owner_manager' || in_array($name, [
            'restaurant.dashboard', 'restaurant.orders.index', 'restaurant.orders.poll', 'restaurant.orders.update',
            ...($role === 'cashier' ? ['restaurant.orders.manual.store', 'restaurant.orders.confirm', 'restaurant.service-requests.index', 'restaurant.service-requests.update'] : []),
        ], true);
        check($run($name) === ($allowed ? 204 : 403), "$role: $name");
    }
    check($session->get('staff_profile_role') === $role && $session->get('staff_profile_name') === 'Current name', 'refresh session permissions');
    check($run('restaurant.future-management') === ($role === 'owner_manager' ? 204 : 403), 'unknown route fails closed');
}

foreach (['missing', 'deleted', 'inactive', 'foreign', 'unknown-role', 'kitchen-disabled'] as $case) {
    SecurityProfileFixture::$record = (object) ['id' => 3, 'restaurant_id' => 7, 'is_active' => true, 'role' => 'kitchen', 'name' => 'Kitchen'];
    $session->put(['staff_profile_id' => 3, 'staff_profile_role' => 'owner_manager', 'staff_profile_name' => 'Stale']);
    $restaurant->kitchen = $case !== 'kitchen-disabled';
    match ($case) {
        'missing' => $session->forget('staff_profile_id'),
        'deleted' => SecurityProfileFixture::$record = null,
        'inactive' => SecurityProfileFixture::$record->is_active = false,
        'foreign' => SecurityProfileFixture::$record->restaurant_id = 99,
        'unknown-role' => SecurityProfileFixture::$record->role = 'admin',
        default => null,
    };
    check($run('restaurant.dashboard') === 401, "$case rejected");
    check(! $session->has('staff_profile_id') && ! $session->has('staff_profile_role') && ! $session->has('staff_profile_name'), "$case clears session");
}

foreach ([null, 'staff', 'restaurant_owner'] as $role) {
    $request->setUserResolver(fn () => $role ? (object) ['role' => $role] : null);
    foreach (['show', 'run'] as $action) {
        try {
            (new SetupController)->$action($request);
            throw new RuntimeException('Unauthorized setup reached side effects');
        } catch (HttpException $e) {
            check($e->getStatusCode() === 403, 'setup controller guard');
        }
    }
}

echo "Passed $checks security checks; no application bootstrap or database access.\n";
