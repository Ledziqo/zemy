<?php

require __DIR__.'/../vendor/autoload.php';

use App\Http\Middleware\RouteThrottleRequests;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

$middleware = new RouteThrottleRequests(new RateLimiter(new Repository(new ArrayStore)));
$call = function ($name, $uri, $limit, $user = null, array $parameters = []) use ($middleware) {
    $request = Request::create('https://example.test/'.str_replace('{restaurant_slug}', $parameters['restaurant_slug'] ?? 'hotel-a', $uri));
    $route = (new Route('GET', $uri, fn () => null))->name($name);
    $route->bind($request);
    foreach ($parameters as $key => $value) $route->setParameter($key, $value);
    $request->setRouteResolver(fn () => $route);
    $request->setUserResolver(fn () => $user);
    return $middleware->handle($request, fn () => new Response('ok'), $limit, 5);
};
$admin = new class { public function getAuthIdentifier() { return 7; } };
for ($i = 0; $i < 20; $i++) $call('status', 'status', 60, $admin);
$call('scan', 'scan', 1, $admin);
try {
    $call('scan', 'scan', 1, $admin);
    throw new RuntimeException('Repeated scan was not throttled');
} catch (\Illuminate\Http\Exceptions\ThrottleRequestsException $expected) {}
for ($i = 0; $i < 20; $i++) $call('menu', 'menu', 300);
$call('login', 'login', 10);
$orderLimit = 30;
for ($i = 0; $i < $orderLimit; $i++) $call('orders.store', 'r/{restaurant_slug}/orders', $orderLimit, null, ['restaurant_slug' => 'hotel-a']);
for ($i = 0; $i < $orderLimit; $i++) $call('orders.store', 'r/{restaurant_slug}/orders', $orderLimit, null, ['restaurant_slug' => 'hotel-b']);
try {
    $call('orders.store', 'r/{restaurant_slug}/orders', $orderLimit, null, ['restaurant_slug' => 'hotel-a']);
    throw new RuntimeException('Same hotel exceeded its order limit');
} catch (\Illuminate\Http\Exceptions\ThrottleRequestsException $expected) {}
echo "PASS: unrelated actions and tenants have isolated buckets; each hotel's order limit remains enforced.\n";
