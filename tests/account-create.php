<?php

// Execute the real account creation controller against a strict in-memory SQL transport.
// No production credentials, database connection, or live account writes.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array']);
$session = app('session')->driver();
$session->start();

Illuminate\Support\Facades\Schema::swap(new class {
    public function hasTable($table): bool { return true; }
    public function hasColumn($table, $column): bool { return true; }
});
$connection = new class(null, 'account-test') extends Illuminate\Database\MySqlConnection {
    public array $rows = [];
    private int $nextId = 0;
    public function insert($query, $bindings = [], $sequence = null) {
        if (! preg_match('/insert into `([^`]+)` \(([^)]+)\)/', $query, $matches)) throw new RuntimeException('Unexpected insert');
        $columns = array_map(fn ($value) => trim($value, ' `'), explode(',', $matches[2]));
        $row = array_combine($columns, $bindings);
        if ($matches[1] === 'subscriptions' && empty($row['plan_name'])) {
            throw new RuntimeException("Field 'plan_name' doesn't have a default value");
        }
        $row['id'] = ++$this->nextId;
        $this->rows[$matches[1]][] = $row;
        return true;
    }
    public function getLastInsertId() { return $this->nextId; }
    public function transaction(Closure $callback, $attempts = 1) {
        $before = $this->rows;
        try { return $callback($this); }
        catch (Throwable $exception) { $this->rows = $before; throw $exception; }
    }
    public function select($query, $bindings = [], $useReadPdo = true) { throw new RuntimeException('Unexpected read: '.$query); }
};
Illuminate\Support\Facades\DB::extend('account_fixture', fn () => $connection);
config(['database.default' => 'account_fixture', 'database.connections.account_fixture' => ['driver' => 'account_fixture']]);
$checks = 0;
$check = function (bool $value, string $message) use (&$checks) {
    if (! $value) throw new RuntimeException($message);
    $checks++;
};

foreach (['full', 'menu_only'] as $mode) foreach (['none', 'dates', 'price'] as $subscription) {
    $connection->rows = [];
    $request = new class extends Illuminate\Http\Request {
        // Input validation is independent of the subscription SQL regression.
        public function validate(array $rules, ...$args): array { return $this->all(); }
    };
    $request->replace([
        'name' => 'Test hotel', 'slug' => 'account-test', 'business_type' => 'hotel',
        'service_mode' => $mode, 'menu_photos_enabled' => '0', 'is_active' => '1',
        'email' => 'fixture@example.com', 'owner_password' => 'test-owner-password',
        ...match ($subscription) {
            'dates' => ['subscription_starts_at' => '2026-10-14', 'subscription_ends_at' => '2026-11-14'],
            'price' => ['monthly_price' => '1500'],
            default => [],
        },
    ]);
    $request->setLaravelSession($session);
    app()->instance('request', $request);
    $response = (new App\Http\Controllers\Admin\RestaurantController())->store($request);
    $check($response->getStatusCode() === 302, 'Account creation returns successfully');
    $check(count($connection->rows['restaurants'] ?? []) === 1 && count($connection->rows['users'] ?? []) === 1, 'Venue and owner are created');
    $settings = json_decode($connection->rows['restaurants'][0]['settings'], true);
    $check($settings['service_mode'] === $mode && $settings['menu_photos_enabled'] === false, 'Account mode and appearance are retained');
    if ($subscription === 'none') {
        $check(empty($connection->rows['subscriptions']), 'Optional subscription is not created when all fields are empty');
    } else {
        $sub = $connection->rows['subscriptions'][0];
        $check($sub['plan_name'] === ($mode === 'menu_only' ? 'Digital Menu' : 'Pro') && $sub['status'] === 'trial', 'Every subscription supplies its required plan name and trial status');
        $check(! empty($sub['starts_at']), 'Price-only subscription receives a start date');
        if ($subscription === 'dates') $check(substr($sub['starts_at'], 0, 10) === '2026-10-14' && substr($sub['ends_at'], 0, 10) === '2026-11-14', 'Entered dates are preserved');
        if ($subscription === 'price') $check((float) $sub['monthly_price'] === 1500.0, 'Entered price is preserved');
    }
}
echo "Passed {$checks} account-creation checks: full and menu-only accounts, with dates, price, or no subscription fields.\n";
