<?php

namespace App\Http\Controllers;

use App\Models\GuestSession;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SetupController extends Controller
{
    public function show()
    {
        return view('setup.show');
    }

    public function run(Request $request)
    {
        $output = [];
        $this->applySubmittedDatabaseConfig($request, $output);

        try {
            $this->runSetupCommands($output, $request->boolean('seed_demo_data'));
            if ($request->boolean('cleanup_stress_orders')) {
                $this->cleanupStressOrders($output);
            }

            if ($request->is('admin/*')) {
                return redirect()->route('admin.database')->with('setup_output', trim(implode("\n", $output)));
            }
            return view('setup.show', [
                'success' => true,
                'output' => trim(implode("\n", $output)),
            ]);
        } catch (Throwable $exception) {
            if (str_contains($exception->getMessage(), "'@'127.0.0.1'")) {
                try {
                    config(['database.connections.mysql.host' => 'localhost']);
                    DB::purge('mysql');
                    $output[] = 'First database attempt used 127.0.0.1 and failed. Retrying with localhost...';
                    $this->runSetupCommands($output, $request->boolean('seed_demo_data'));
                    if ($request->boolean('cleanup_stress_orders')) {
                        $this->cleanupStressOrders($output);
                    }

                    if ($request->is('admin/*')) {
                        return redirect()->route('admin.database')->with('setup_output', trim(implode("\n", $output)));
                    }
                    return view('setup.show', [
                        'success' => true,
                        'output' => trim(implode("\n", $output)),
                    ]);
                } catch (Throwable $retryException) {
                    $exception = $retryException;
                }
            }

            if ($request->is('admin/*')) {
                return redirect()->route('admin.database')->with('setup_output', $this->friendlyError($exception));
            }
            return view('setup.show', [
                'success' => false,
                'output' => $this->friendlyError($exception),
                'db' => $this->currentDatabaseConfig(),
            ]);
        }
    }

    private function runSetupCommands(array &$output, bool $seedDemoData = false): void
    {
        $this->baselineExistingDatabase($output);

        $output[] = 'Applying database migrations and updates...';
        Artisan::call('migrate', ['--force' => true]);
        $output[] = Artisan::output();

        if ($seedDemoData) {
            $output[] = 'Refreshing default demo/admin data...';
            Artisan::call('db:seed', ['--force' => true]);
            $output[] = Artisan::output();
        }

        $output[] = 'Clearing cached config/routes/views...';
        Artisan::call('optimize:clear');
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

    private function updateEnv(array $updates): void
    {
        $path = base_path('.env');
        if (! File::exists($path) || ! File::isWritable($path)) {
            return;
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
