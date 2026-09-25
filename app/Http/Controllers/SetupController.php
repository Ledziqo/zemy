<?php

namespace App\Http\Controllers;

use App\Models\GuestSession;
use App\Models\Order;
use Database\Seeders\StressTestSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SetupController extends Controller
{
    public function show(Request $request)
    {
        $stagingBootstrap = $this->stagingBootstrapAllowed();
        abort_unless($stagingBootstrap || $request->user()?->role === 'admin', 403);

        return view('setup.show', compact('stagingBootstrap'));
    }

    public function run(Request $request)
    {
        // Guard before configuration changes or commands, including with stale route caches.
        $stagingBootstrap = $this->stagingBootstrapAllowed();
        abort_unless($stagingBootstrap || $request->user()?->role === 'admin', 403);

        if (app()->environment('production')) {
            abort_if($request->boolean('seed_demo_data'),
                403, 'Demo seeding is disabled in production.');
            abort_if($request->boolean('seed_stress_data') && ! config('stress.allow_production'),
                403, 'Enable the temporary production stress-test mode first.');

            // Web maintenance must not run historical demo provisioning, even with CLI opt-in.
            config(['demo.allow_production_migrations' => false]);
        }

        $output = [];

        try {
            $this->applySubmittedDatabaseConfig($request, $output);

            if ($request->input('stress_mode') === 'enable') {
                $this->setStressTestMode(true, $output);
            } elseif ($request->input('stress_mode') === 'disable') {
                if ($request->boolean('cleanup_stress_data')) {
                    $this->cleanupStressData($output);
                }
                $this->setStressTestMode(false, $output);
            } elseif ($request->boolean('seed_stress_data')) {
                $batch = (int) $request->integer('stress_batch', 1);
                $this->seedStressData($output, $batch);
            } elseif ($request->boolean('cleanup_stress_data')) {
                $this->cleanupStressData($output);
            } else {
                // A brand-new staging database has no admin account yet, so seed
                // the admin/demo records automatically during the one-time bootstrap.
                $this->runSetupCommands($output, $stagingBootstrap || $request->boolean('seed_demo_data'));
                if ($request->boolean('cleanup_stress_orders')) {
                    $this->cleanupStressOrders($output);
                }
            }

            if ($request->is('admin/*')) {
                return $this->adminMaintenanceResponse(trim(implode("\n", $output)), true);
            }
            return view('setup.show', [
                'success' => true,
                'output' => trim(implode("\n", $output)),
                'stagingBootstrap' => false,
            ]);
        } catch (Throwable $exception) {
            if ($request->is('admin/*')) {
                return $this->adminMaintenanceResponse($this->friendlyError($exception), false);
            }
            return view('setup.show', [
                'success' => false,
                'output' => $this->friendlyError($exception),
                'db' => $this->currentDatabaseConfig(),
                'stagingBootstrap' => $stagingBootstrap,
            ]);
        }
    }

    private function stagingBootstrapAllowed(): bool
    {
        if (! app()->bound('config')) {
            return false;
        }

        return (string) config('app.env') === 'staging'
            && (bool) config('app.debug')
            && ! Schema::hasTable('users');
    }

    private function runSetupCommands(array &$output, bool $seedDemoData = false): void
    {
        $output[] = 'Clearing cached config/routes/views...';
        Artisan::call('optimize:clear');
        $output[] = Artisan::output();

        $this->baselineExistingDatabase($output);

        $output[] = 'Applying database migrations and updates...';
        Artisan::call('migrate', ['--force' => true]);
        $output[] = Artisan::output();

        if ($seedDemoData) {
            $output[] = 'Refreshing default demo/admin data...';
            Artisan::call('db:seed', ['--force' => true]);
            $output[] = Artisan::output();
        }

        $this->rebuildRuntimeCaches($output);
    }

    private function seedStressData(array &$output, int $batch): void
    {
        $batchSize = (int) config('stress.batch_size', 10);
        $maxRestaurants = (int) config('stress.max_restaurants', 150);
        abort_unless($batch >= 1 && (($batch - 1) * $batchSize) < $maxRestaurants, 422, 'That stress-test batch is outside the allowed range.');
        $start = ($batch - 1) * $batchSize + 1;
        $end = min($batch * $batchSize, $maxRestaurants);
        $output[] = "Seeding stress test batch {$batch} (restaurants {$start}-{$end})...";

        // Set env for the seeder to read
        $_ENV['STRESS_BATCH'] = $batch;
        $_ENV['STRESS_BATCH_SIZE'] = $batchSize;

        Artisan::call('db:seed', ['--class' => 'StressTestSeeder', '--force' => true]);
        $output[] = Artisan::output();
        $output[] = "Batch {$batch} complete. Restaurants {$start}-{$end} created.";
    }

    private function cleanupStressData(array &$output): void
    {
        $output[] = 'Cleaning up stress test data...';
        StressTestSeeder::cleanup();
        $output[] = 'Stress test restaurants, users, orders, and related data removed.';
    }

    public function refreshTulipMenu(Request $request)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $output = [];

        try {
            $this->applySubmittedDatabaseConfig($request, $output);

            $output[] = 'Applying the Tulip Olympia menu update...';
            Artisan::call('migrate', ['--force' => true]);
            $output[] = Artisan::output();

            // This migration can update menu rows directly, so discard cached
            // public-menu snapshots and Work Board revisions without touching
            // a possibly DB-backed default cache store.
            Cache::store('file')->flush();

            $this->rebuildRuntimeCaches($output);

            return $this->adminMaintenanceResponse(trim(implode("\n", $output)), true);
        } catch (Throwable $exception) {
            return $this->adminMaintenanceResponse($this->friendlyError($exception), false);
        }
    }

    private function adminMaintenanceResponse(string $output, bool $success)
    {
        $title = $success ? 'Maintenance completed' : 'Maintenance failed';
        $message = $success
            ? 'The server finished the requested migration and cache work.'
            : 'The server returned an error while running maintenance.';
        $escapedOutput = htmlspecialchars($output, ENT_QUOTES, 'UTF-8');

        return response('<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.$title.' · ZemTab</title><style>body{margin:0;background:#0f1115;color:#f5f7fa;font-family:system-ui,sans-serif}main{max-width:760px;margin:12vh auto;padding:24px}section{border:1px solid '.($success ? '#34d399' : '#f87171').';border-radius:14px;background:#181b22;padding:24px}h1{font-size:24px;margin:0 0 8px}p{color:#a7b0be}pre{white-space:pre-wrap;overflow:auto;border:1px solid #303541;border-radius:8px;background:#0f1115;padding:16px;color:#a7b0be}a{display:inline-block;margin-top:16px;padding:12px 18px;border-radius:8px;background:#d22630;color:#fff;text-decoration:none;font-weight:700}</style></head><body><main><section><h1>'.$title.'</h1><p>'.$message.'</p><pre>'.$escapedOutput.'</pre><a href="/admin/database">Back to database maintenance</a></section></main></body></html>');
    }

    private function rebuildRuntimeCaches(array &$output): void
    {
        $output[] = 'Rebuilding production config, route, and view caches...';
        foreach (['config:cache', 'route:cache', 'view:cache'] as $command) {
            Artisan::call($command);
            $output[] = Artisan::output();
        }
    }

    private function setStressTestMode(bool $enabled, array &$output): void
    {
        if (! $this->updateEnv(['STRESS_TEST_ALLOW_PRODUCTION' => $enabled ? 'true' : 'false'])) {
            throw new \LogicException('The production .env file is missing or not writable. Enable or disable stress mode from your hosting file manager, then clear the config cache.');
        }

        config(['stress.allow_production' => $enabled]);
        Artisan::call('optimize:clear');
        $output[] = $enabled
            ? 'Production stress mode enabled. You can now add test tenants in batches.'
            : 'Production stress mode disabled. Normal production protections are restored.';
        $output[] = Artisan::output();
    }

    private function friendlyError(Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'Access denied for user')) {
            return $message."\n\nThis is a database login problem. In your hosting panel, confirm DB_DATABASE, DB_USERNAME, DB_PASSWORD, and DB_HOST. On Hostinger-style shared hosting, DB_HOST is usually localhost, not 127.0.0.1.";
        }

        return $message;
    }

    private function applySubmittedDatabaseConfig(Request $request, array &$output): void
    {
        $data = $request->validate([
            'db_host' => ['nullable', 'string', 'max:255'],
            'db_database' => ['nullable', 'string', 'max:255'],
            'db_username' => ['nullable', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ]);

        $updates = [];
        foreach ([
            'DB_HOST' => 'db_host',
            'DB_DATABASE' => 'db_database',
            'DB_USERNAME' => 'db_username',
            'DB_PASSWORD' => 'db_password',
        ] as $envKey => $inputKey) {
            if (($data[$inputKey] ?? '') !== '') {
                $updates[$envKey] = $data[$inputKey];
            }
        }

        if ($updates === []) {
            return;
        }

        $this->updateEnv($updates);

        config([
            'database.connections.mysql.host' => $updates['DB_HOST'] ?? config('database.connections.mysql.host'),
            'database.connections.mysql.database' => $updates['DB_DATABASE'] ?? config('database.connections.mysql.database'),
            'database.connections.mysql.username' => $updates['DB_USERNAME'] ?? config('database.connections.mysql.username'),
            'database.connections.mysql.password' => $updates['DB_PASSWORD'] ?? config('database.connections.mysql.password'),
        ]);
        DB::purge('mysql');

        $output[] = 'Database settings were saved before setup ran.';
    }

    private function updateEnv(array $updates): bool
    {
        $path = base_path('.env');
        if (! File::exists($path) || ! File::isWritable($path)) {
            return false;
        }

        $contents = File::get($path);

        foreach ($updates as $key => $value) {
            $line = $key.'='.$this->escapeEnvValue($value);
            if (preg_match('/^'.$key.'=.*/m', $contents)) {
                $contents = preg_replace('/^'.$key.'=.*/m', $line, $contents);
            } else {
                $contents .= PHP_EOL.$line;
            }
        }

        File::put($path, $contents);

        return true;
    }

    private function escapeEnvValue(string $value): string
    {
        return preg_match('/\s|#|"|\'/', $value) ? '"'.str_replace('"', '\"', $value).'"' : $value;
    }

    private function currentDatabaseConfig(): array
    {
        return [
            'host' => config('database.connections.mysql.host'),
            'database' => config('database.connections.mysql.database'),
            'username' => config('database.connections.mysql.username'),
        ];
    }

    private function cleanupStressOrders(array &$output): void
    {
        $sessionIds = [];
        $orderCount = 0;

        Order::where('note', 'stress test order')
            ->select(['id', 'guest_session_id'])
            ->chunkById(200, function ($orders) use (&$sessionIds, &$orderCount) {
                foreach ($orders as $order) {
                    if ($order->guest_session_id) {
                        $sessionIds[] = $order->guest_session_id;
                    }
                    $order->items()->delete();
                    $order->delete();
                    $orderCount++;
                }
            });

        $sessionIds = array_values(array_unique($sessionIds));
        $sessionCount = $sessionIds === []
            ? 0
            : GuestSession::whereIn('id', $sessionIds)
                ->whereDoesntHave('orders')
                ->whereDoesntHave('serviceRequests')
                ->whereDoesntHave('payments')
                ->delete();

        $output[] = "Removed {$orderCount} stress-test orders and {$sessionCount} empty guest sessions.";
    }

    private function baselineExistingDatabase(array &$output): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasTable('migrations')) {
            Schema::create('migrations', function ($table) {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
            });
        }

        $knownMigrations = [
            '0001_01_01_000000_create_users_table' => ['users', 'password_reset_tokens', 'sessions'],
            '0001_01_01_000001_create_cache_table' => ['cache', 'cache_locks'],
            '0001_01_01_000002_create_jobs_table' => ['jobs', 'job_batches', 'failed_jobs'],
            '2026_06_01_000000_create_zemtab_tables' => ['restaurants', 'categories', 'menu_items', 'restaurant_tables', 'orders', 'order_items', 'service_requests', 'demo_requests', 'subscriptions', 'payments'],
        ];

        $batch = (int) DB::table('migrations')->max('batch') ?: 1;

        foreach ($knownMigrations as $migration => $tables) {
            if (DB::table('migrations')->where('migration', $migration)->exists()) {
                continue;
            }

            $anyTableExists = collect($tables)->contains(fn ($table) => Schema::hasTable($table));
            if ($anyTableExists) {
                DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => $batch,
                ]);
                $output[] = 'Marked existing migration as complete: '.$migration;
            }
        }

        $knownColumnMigrations = [
            '2026_06_03_000000_add_dashboard_access_to_restaurants' => ['restaurants', 'dashboard_access_status'],
            '2026_06_05_000000_add_business_type_to_restaurants' => ['restaurants', 'business_type'],
            '2026_07_16_000000_add_kitchen_screen_setting' => ['restaurants', 'kitchen_screen_enabled'],
            '2026_06_26_000000_add_business_type_to_demo_requests' => ['demo_requests', 'business_type'],
        ];

        foreach ($knownColumnMigrations as $migration => [$table, $column]) {
            if (DB::table('migrations')->where('migration', $migration)->exists()) {
                continue;
            }

            if (Schema::hasColumn($table, $column)) {
                DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => $batch,
                ]);
                $output[] = 'Marked existing column migration as complete: '.$migration;
            }
        }
    }
}
