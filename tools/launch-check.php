<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__.'/launch-fixture.php';
$preview = in_array('--setup-preview', $argv, true);
$app = launchApp($preview ? 'preview' : 'checks');
$fixture = launchSeed();
if ($preview) {
    echo "Preview fixture ready. Login: aurora@launch.test or admin@launch.test\nAccount and all staff profile passwords: {$fixture['password']}\n";
    exit;
}

// Full Laravel HTTP kernel, real session cookies and real login/profile handlers.
// Testing environment disables CSRF here; preview separately keeps CSRF enabled.
final class LaunchClient
{
    private array $cookies = [];
    public function request(string $method, string $url, array $data = [], bool $json = false): \Symfony\Component\HttpFoundation\Response
    {
        global $app;
        $app['auth']->forgetGuards();
        $app['session']->forgetDrivers();
        $app->forgetInstance('session.store');
        $app['cache']->flush();
        $request = \Illuminate\Http\Request::create('http://127.0.0.1:8091'.$url, $method, $data, $this->cookies, [], ['HTTP_ACCEPT' => $json ? 'application/json' : 'text/html', 'REMOTE_ADDR' => '127.0.0.1']);
        $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
        $response = $kernel->handle($request);
        foreach ($response->headers->getCookies() as $cookie) $this->cookies[$cookie->getName()] = $cookie->getValue();
        $kernel->terminate($request, $response);
        return $response;
    }
    public function login(string $email, string $password, ?int $profile = null): void
    {
        $response = $this->request('POST', '/login', compact('email', 'password'));
        check('Login '.$email, $response->getStatusCode() === 302 && str_ends_with($response->headers->get('Location', ''), $email === 'admin@launch.test' ? '/admin/dashboard' : '/restaurant/profile-select'));
        if ($profile !== null) {
            $response = $this->request('POST', '/restaurant/profile-login', ['profile_id' => $profile, 'password' => $password]);
            check('Profile login '.$profile, $response->getStatusCode() === 302 && str_ends_with($response->headers->get('Location', ''), '/restaurant/dashboard'));
        }
    }
}
$results = [];
function check(string $name, bool $ok, string $detail = ''): void
{
    global $results;
    $results[] = compact('name', 'ok', 'detail');
    echo ($ok ? 'PASS ' : 'FAIL ').$name.($detail ? ' — '.$detail : '').PHP_EOL;
}
function status(string $name, $response, array $expected): void
{
    check($name, in_array($response->getStatusCode(), $expected, true), 'HTTP '.$response->getStatusCode());
}
// Roll back all test mutations, leaving repeatable fixture and independent preview intact.
\Illuminate\Support\Facades\DB::beginTransaction();
try {
    $a = $fixture['tenants']['aurora']; $b = $fixture['tenants']['birch']; $pw = $fixture['password'];
    foreach (['restaurants' => ['dashboard_access_status', 'kitchen_screen_enabled', 'menu_cache_version'], 'orders' => ['guest_session_id', 'handled_by_profile_id', 'order_type', 'confirmed_at'], 'menu_items' => ['image_source_url'], 'staff_profiles' => ['disabled_by_kitchen_mode']] as $table => $columns) {
        check('Schema '.$table, \Illuminate\Support\Facades\Schema::hasColumns($table, $columns));
    }
    check('SQLite foreign keys enabled', (int) \Illuminate\Support\Facades\DB::selectOne('PRAGMA foreign_keys')->foreign_keys === 1);
    check('Fixture foreign key integrity', \Illuminate\Support\Facades\DB::select('PRAGMA foreign_key_check') === []);
    $guest = new LaunchClient();
    status('Database readiness endpoint succeeds', $guest->request('GET', '/ready'), [200]);
    status('Landing page renders', $guest->request('GET', '/'), [200]);
    $demoResponse = $guest->request('GET', '/demo');
    status('Public interactive demo renders', $demoResponse, [200]);
    check('Demo identifies sample-only behavior', str_contains($demoResponse->getContent(), 'No real orders or payments.'));
    status('Public setup requires login', $guest->request('GET', '/setup', [], true), [401]);
    $sitemap = $guest->request('GET', '/sitemap.xml');
    status('Public sitemap renders', $sitemap, [200]);
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($sitemap->getContent());
    check('Sitemap valid XML and content type', $xml !== false && $xml->getName() === 'urlset' && str_contains($sitemap->headers->get('Content-Type', ''), 'xml'));
    check('Sitemap contains fictional table URL', str_contains($sitemap->getContent(), '/r/launch-aurora/table/1'));
    status('Unauthenticated dashboard denied', $guest->request('GET', '/restaurant/dashboard', [], true), [401]);
    status('Unauthenticated admin denied', $guest->request('GET', '/admin/dashboard', [], true), [401]);
    $guest->request('POST', '/login', ['email' => $a['email'], 'password' => 'wrong'], true);
    status('Invalid password leaves user unauthenticated', $guest->request('GET', '/restaurant/dashboard', [], true), [401]);
    status('Public menu renders', $guest->request('GET', '/r/launch-aurora/table/1'), [200]);
    $before = \App\Models\Order::count();
    $guest->request('POST', '/r/launch-aurora/table/1/orders', ['items' => [['id' => $b['item'], 'quantity' => 1]]]);
    check('Guest cannot order other tenant item', \App\Models\Order::count() === $before);
    $category = \App\Models\MenuItem::find($a['item'])->category;
    $category->update(['is_active' => false]);
    $guest->request('POST', '/r/launch-aurora/table/1/orders', ['items' => [['id' => $a['item'], 'quantity' => 1]]]);
    check('Guest cannot order item from inactive category', \App\Models\Order::count() === $before);
    $category->update(['is_active' => true]);
    status('Zero quantity rejected', $guest->request('POST', '/r/launch-aurora/table/1/orders', ['items' => [['id' => $a['item'], 'quantity' => 0]]], true), [422]);
    status('Guest valid order accepted', $guest->request('POST', '/r/launch-aurora/table/1/orders', ['items' => [['id' => $a['item'], 'quantity' => 2, 'price' => 1]], 'total' => 1]), [302]);
    $order = \App\Models\Order::latest('id')->first();
    check('Server calculates prices and taxes', $order->restaurant_id === $a['restaurant'] && (float) $order->total === 250.0 && $order->items()->count() === 1);
    check('Guest order requires confirmation', $order->confirmed_at === null && $order->payment_status === 'unpaid');
    $stranger = new LaunchClient();
    status('Other guest cannot cancel order', $stranger->request('PATCH', '/r/launch-aurora/table/1/orders/'.$order->id.'/cancel', [], true), [404]);
    $clients = [];
    foreach (['owner_manager', 'cashier', 'kitchen'] as $role) {
        $client = $clients[$role] = new LaunchClient();
        $client->login($a['email'], $pw, $a['profiles'][$role]);
        foreach (['dashboard', 'orders'] as $page) {
            $response = $client->request('GET', '/restaurant/'.$page);
            status($role.' '.$page.' renders', $response, [200]);
            file_put_contents(dirname(config('database.connections.sqlite.database')).'/'.$role.'-'.$page.'.html', $response->getContent());
            check($role.' '.$page.' excludes other tenant', $response->getStatusCode() === 200 && !str_contains($response->getContent(), 'Birch lentil bowl'));
        }
        status($role.' cannot access admin', $client->request('GET', '/admin/dashboard', [], true), [403]);
        status($role.' cross-tenant mutation rejected', $client->request('PATCH', '/restaurant/orders/'.$b['orders']['new'], ['status' => 'cancelled'], true), [403, 404]);
    }
    $owner = $clients['owner_manager']; $cashier = $clients['cashier']; $kitchen = $clients['kitchen'];
    foreach (['settings', 'menu-items', 'categories', 'staff-profiles', 'analytics', 'cashier-reports'] as $page) {
        status('Cashier denied '.$page, $cashier->request('GET', '/restaurant/'.$page, [], true), [403]);
        status('Kitchen denied '.$page, $kitchen->request('GET', '/restaurant/'.$page, [], true), [403]);
    }
    foreach (['cashier' => $cashier, 'kitchen' => $kitchen] as $role => $client) {
        status($role.' cannot write settings', $client->request('PATCH', '/restaurant/settings', [], true), [403]);
        status($role.' cannot toggle menu item', $client->request('PATCH', '/restaurant/menu-items/'.$a['item'].'/availability', [], true), [403]);
    }
    status('Owner settings renders', $owner->request('GET', '/restaurant/settings'), [200]);
    status('Owner menu renders', $owner->request('GET', '/restaurant/menu-items'), [200]);
    foreach (['analytics', 'cashier-reports', 'categories', 'tables', 'staff-profiles', 'delivery'] as $page) {
        status('Owner '.$page.' renders', $owner->request('GET', '/restaurant/'.$page), [200]);
    }
    $qr = $owner->request('GET', '/restaurant/tables/'.$a['table'].'/qr');
    check('QR code is valid SVG', $qr->getStatusCode() === 200 && simplexml_load_string($qr->getContent()) !== false);
    $invalidTableCount = \App\Models\Order::count();
    status('Manual order rejects nonexistent table', $cashier->request('POST', '/restaurant/orders/manual', ['order_mode' => 'table', 'table_number' => 'missing', 'items' => [['id' => $a['item'], 'quantity' => 1]]], true), [422]);
    check('Invalid table creates no order', \App\Models\Order::count() === $invalidTableCount);
    $oldActive = \App\Models\Order::find($a['orders']['new']);
    $oldActive->update(['created_at' => now()->subDays(2)]);
    for ($i = 0; $i < 35; $i++) \App\Models\Order::create(['restaurant_id' => $a['restaurant'], 'table_number' => '1', 'status' => 'completed', 'total' => 0, 'subtotal' => 0, 'payment_status' => 'paid', 'confirmed_at' => now()]);
    $queue = json_decode($cashier->request('GET', '/restaurant/orders/poll', [], true)->getContent(), true);
    check('Older active order survives newer completed history', in_array($oldActive->id, array_column($queue['orders'] ?? [], 'id'), true));
    check('Active queue excludes completed history', !in_array('completed', array_column($queue['orders'] ?? [], 'status'), true));
    status('Owner cannot toggle foreign menu item', $owner->request('PATCH', '/restaurant/menu-items/'.$b['item'].'/availability', [], true), [403, 404]);
    $poll = json_decode($kitchen->request('GET', '/restaurant/orders/poll', [], true)->getContent(), true);
    check('Kitchen poll hides unconfirmed order', is_array($poll) && !in_array($order->id, array_column($poll['orders'] ?? [], 'id'), true));
    check('Kitchen poll excludes other tenant', is_array($poll) && array_intersect(array_values($b['orders']), array_column($poll['orders'] ?? [], 'id')) === []);
    status('Kitchen cannot confirm guest order', $kitchen->request('PATCH', '/restaurant/orders/'.$order->id.'/confirm', [], true), [403]);
    status('Cashier cannot advance unconfirmed order', $cashier->request('PATCH', '/restaurant/orders/'.$order->id, ['status' => 'paid'], true), [403, 422]);
    status('Cashier confirms guest order', $cashier->request('PATCH', '/restaurant/orders/'.$order->id.'/confirm', [], true), [200]);
    status('Kitchen cannot collect payment', $kitchen->request('PATCH', '/restaurant/orders/'.$a['orders']['served'], ['status' => 'paid'], true), [403, 422]);
    check('Forbidden payment leaves order unchanged', \App\Models\Order::find($a['orders']['served'])->status === 'served');
    status('Cashier cannot pay before served', $cashier->request('PATCH', '/restaurant/orders/'.$a['orders']['preparing'], ['status' => 'paid'], true), [403, 422]);
    status('Cashier pays served order', $cashier->request('PATCH', '/restaurant/orders/'.$a['orders']['served'], ['status' => 'paid', 'payment_method' => 'cash'], true), [200]);
    $paid = \App\Models\Order::find($a['orders']['served']);
    check('Payment attributed to cashier', $paid->payment_status === 'paid' && $paid->handled_by_profile_id === $a['profiles']['cashier']);
    $count = \App\Models\Order::count();
    status('Kitchen cannot create manual order', $kitchen->request('POST', '/restaurant/orders/manual', ['order_mode' => 'takeaway', 'items' => [['id' => $a['item'], 'quantity' => 1]]], true), [403]);
    check('Denied manual order creates nothing', \App\Models\Order::count() === $count);
    $cashier->request('POST', '/restaurant/orders/manual', ['order_mode' => 'takeaway', 'items' => [['id' => $b['item'], 'quantity' => 1]]]);
    check('Manual order rejects other tenant item', \App\Models\Order::count() === $count);
    status('Cashier creates valid manual order', $cashier->request('POST', '/restaurant/orders/manual', ['order_mode' => 'takeaway', 'items' => [['id' => $a['item'], 'quantity' => 1]]]), [302]);
    $manual = \App\Models\Order::latest('id')->first();
    check('Manual order persisted with trusted price and confirmation', \App\Models\Order::count() === $count + 1 && (float) $manual->total === 125.0 && $manual->confirmed_at !== null);
    status('Kitchen starts confirmed order', $kitchen->request('PATCH', '/restaurant/orders/'.$manual->id, ['status' => 'preparing'], true), [200]);
    status('Kitchen serves prepared order', $kitchen->request('PATCH', '/restaurant/orders/'.$manual->id, ['status' => 'served'], true), [200]);
    $guest->request('POST', '/r/launch-aurora/table/1/orders', ['items' => [['id' => $a['item'], 'quantity' => 1]]]);
    $cancel = \App\Models\Order::latest('id')->first();
    status('Guest cancels own recent order', $guest->request('PATCH', '/r/launch-aurora/table/1/orders/'.$cancel->id.'/cancel'), [302]);
    check('Guest cancellation persisted', $cancel->fresh()->status === 'cancelled');
    status('Cancelled order cannot be confirmed', $cashier->request('PATCH', '/restaurant/orders/'.$cancel->id.'/confirm', [], true), [422]);
    $unselected = new LaunchClient(); $unselected->login($a['email'], $pw);
    $unselected->request('POST', '/restaurant/profile-login', ['profile_id' => $b['profiles']['owner_manager'], 'password' => $pw], true);
    status('Foreign profile selection grants no access', $unselected->request('GET', '/restaurant/orders', [], true), [401, 403]);
    $owner->request('POST', '/login', ['email' => $a['email'], 'password' => $pw]);
    status('Account login clears previous profile', $owner->request('GET', '/restaurant/orders', [], true), [401, 403]);
    $owner->login($a['email'], $pw, $a['profiles']['owner_manager']);
    \App\Models\StaffProfile::find($a['profiles']['owner_manager'])->update(['role' => 'cashier']);
    status('Changed profile role immediately loses management access', $owner->request('GET', '/restaurant/settings', [], true), [403]);
    \App\Models\StaffProfile::find($a['profiles']['owner_manager'])->update(['role' => 'owner_manager']);
    \App\Models\Restaurant::find($a['restaurant'])->update(['dashboard_access_status' => 'revoked']);
    $denied = $owner->request('GET', '/restaurant/orders');
    check('Revoked restaurant access denied', $denied->getStatusCode() === 302 && str_ends_with($denied->headers->get('Location', ''), '/restaurant/access-required'));
    \App\Models\Restaurant::find($a['restaurant'])->update(['dashboard_access_status' => 'active']);
    \App\Models\Restaurant::find($a['restaurant'])->update(['is_active' => false]);
    status('Inactive restaurant immediately loses access', $owner->request('GET', '/restaurant/orders', [], true), [403]);
    \App\Models\Restaurant::find($a['restaurant'])->update(['is_active' => true]);
    \App\Models\StaffProfile::find($a['profiles']['cashier'])->update(['is_active' => false]);
    status('Disabled profile session revoked', $cashier->request('GET', '/restaurant/orders', [], true), [302, 401, 403]);
    \App\Models\Restaurant::find($a['restaurant'])->update(['kitchen_screen_enabled' => false]);
    status('Disabled kitchen session revoked', $kitchen->request('GET', '/restaurant/orders', [], true), [302, 401, 403]);
    $admin = new LaunchClient(); $admin->login('admin@launch.test', $pw);
    $response = $admin->request('GET', '/admin/dashboard');
    status('Admin dashboard renders', $response, [200]);
    foreach (['restaurants', 'users', 'subscriptions', 'payments', 'payment-settings', 'demo-requests', 'database'] as $page) {
        status('Admin '.$page.' renders', $admin->request('GET', '/admin/'.$page), [200]);
    }
    file_put_contents(dirname(config('database.connections.sqlite.database')).'/admin-dashboard.html', $response->getContent());
    $admin->request('POST', '/logout');
    status('Logout revokes authenticated access', $admin->request('GET', '/admin/dashboard', [], true), [401]);
    $app->instance('env', 'production');
    $usersBeforeSeed = \App\Models\User::count();
    (new \Database\Seeders\DatabaseSeeder())->run();
    check('Production default seeder provisions no demo accounts', \App\Models\User::count() === $usersBeforeSeed);
    check('Production historical data migrations disabled by default', !\App\Support\DemoProvisioning::migrationsAllowed());
    try { (new \Database\Seeders\StressTestSeeder())->run(); check('Production stress seeder blocked', false); }
    catch (\LogicException $expected) { check('Production stress seeder blocked', true); }
    $app->instance('env', 'testing');
} finally {
    \Illuminate\Support\Facades\DB::rollBack();
}
$failures = count(array_filter($results, fn ($r) => !$r['ok']));
file_put_contents(dirname(config('database.connections.sqlite.database')).'/results.json', json_encode($results, JSON_PRETTY_PRINT));
echo count($results).' checks; '.$failures." failures.\n";
exit($failures ? 1 : 0);
