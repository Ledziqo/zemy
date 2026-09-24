<?php

namespace App\Support;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

/** Bounded diagnostics and rollback-only workflow integration tests. */
class ReleaseReadinessScanner
{
    public function run(): array
    {
        $started = hrtime(true);
        $checks = [];
        $inventory = [];
        $sqlQueries = 0;
        $sqlMilliseconds = 0.0;

        DB::listen(function (QueryExecuted $query) use (&$sqlQueries, &$sqlMilliseconds): void {
            $sqlQueries++;
            $sqlMilliseconds += $query->time;
        });

        $this->scanRuntime($checks);
        $this->scanDatabase($checks);
        $this->scanRoutes($checks, $inventory);
        $this->scanViews($checks, $inventory);
        $this->scanAssetsAndStorage($checks, $inventory);
        $this->scanRecentLogs($checks);
        $checks = array_merge($checks, app(ReleaseWorkflowTest::class)->run());
        $inventory['capacity_simulation'] = app(ReleaseCapacitySimulation::class)->run();
        $this->add($checks, 'Capacity model', 'Staged busy-hotel simulation', 'INFO', 'Calculated cache/file and database-backed activity for 1, 5, 10, 20, 30, 40 and 80 busy hotels without generating production traffic. The full model is in the report.');
        foreach (['Guest checkout and duplicate submission', 'Menu imports, photos and uploads', 'Staff login, session expiry and role middleware', 'QR editor drag/resize/save and printed visual layout', 'Service requests and background-tab audio alerts', 'Admin settings, billing and workboard reset', 'Mobile, slow network and browser button interactions'] as $workflow) {
            $this->add($checks, 'Coverage gaps', $workflow, 'NOT RUN', 'Requires an isolated end-to-end browser test environment. Source checks and controller simulations do not certify this workflow.');
        }
        $inventory['routes'] = array_map(fn ($row) => array_merge($row, ['http_test_status' => 'NOT RUN']), $inventory['routes'] ?? []);
        $this->add($checks, 'Capacity', 'Concurrent hotel load test', 'NOT RUN', 'This button does not generate production traffic. Run the corrected external load-test harness separately with staged, disposable tenants.');
        $this->add($checks, 'Hosting limits', 'Exact per-account hourly MySQL connection quota', 'NOT AVAILABLE', 'Shared hosting may not expose this quota. Any global MySQL counters are server-wide, not ZemTab-only.');

        $statuses = array_column($checks, 'status');
        $overall = in_array('FAIL', $statuses, true)
            ? 'NO-GO'
            : (count(array_intersect($statuses, ['WARN', 'NOT RUN', 'NOT AVAILABLE'])) > 0 ? 'REVIEW REQUIRED' : 'PASS');

        return [
            'report' => 'ZemTab release diagnostics and isolated workflow tests',
            'generated_at' => now()->toIso8601String(),
            'application' => ['environment' => app()->environment(), 'laravel' => app()->version(), 'php' => PHP_VERSION],
            'overall' => $overall,
            'duration_ms' => round((hrtime(true) - $started) / 1_000_000, 1),
            'database_work' => ['diagnostic_queries' => $sqlQueries, 'query_time_ms' => round($sqlMilliseconds, 1)],
            'connection_budget' => [
                'hosting_limit_per_hour' => 500,
                'planning_budget_per_hour' => 350,
                'reserve_per_hour' => 150,
                'actual_account_connections_this_hour' => null,
                'measured_hotel_capacity' => null,
                'warning' => 'Queries are not connections. This in-process suite reuses the default connection; it does not measure connection creation across PHP workers or concurrent hotel traffic. No capacity certification is implied.',
                'scenarios' => array_map(fn ($hotels) => ['busy_hotels' => $hotels, 'available_connections_per_hotel_hour' => round(350 / $hotels, 2)], [10, 30, 40, 80]),
                'uncached_polling_example' => ['seconds' => 30, 'connections_per_screen_hour_if_each_poll_opens_one' => 120],
            ],
            'inventory' => $inventory,
            'checks' => $checks,
            'important' => 'PASS applies only to each listed assertion. Controller tests bypass HTTP middleware and browser interaction. Untested workflows prevent full release certification. Timing is for this small synthetic dataset, not concurrent production capacity.',
        ];
    }

    private function scanRuntime(array &$checks): void
    {
        $production = app()->environment('production');
        $debug = (bool) config('app.debug');
        $this->add($checks, 'Runtime', 'Production debug mode', ! $production || ! $debug ? 'PASS' : 'FAIL', $production && $debug ? 'APP_DEBUG is enabled in production.' : ($production ? 'Debug output is disabled in production.' : 'Current environment is '.app()->environment().'; production-only settings need a live scan.'));
        if (! $production) {
            $this->add($checks, 'Runtime', 'Production environment', 'WARN', 'Scan is outside production; Hostinger-specific settings cannot be certified here.');
        }

        $keyPresent = (string) config('app.key') !== '';
        $this->add($checks, 'Runtime', 'Application encryption key configured', $keyPresent ? 'PASS' : 'FAIL', $keyPresent ? 'Configured (value intentionally hidden).' : 'APP_KEY is empty.');
        $this->add($checks, 'Runtime', 'HTTPS session cookie', config('session.secure') ? 'PASS' : ($production ? 'FAIL' : 'WARN'), config('session.secure') ? 'Secure session cookies are enabled.' : 'Secure session cookies are disabled.');

        $memoryLimit = ini_get('memory_limit') ?: 'unknown';
        $memoryBytes = $this->iniBytes($memoryLimit);
        $memoryStatus = $memoryBytes !== null && $memoryBytes > 0 && $memoryBytes < 128 * 1024 * 1024 ? 'WARN' : 'INFO';
        $this->add($checks, 'Runtime', 'PHP resource limits', $memoryStatus, 'memory_limit='.$memoryLimit.'; max_execution_time='.(ini_get('max_execution_time') ?: 'unknown').'s; scan peak='.round(memory_get_peak_usage(true) / 1024 / 1024, 1).' MiB. PHP-FPM worker utilization is not exposed.');
        $this->add($checks, 'Runtime', 'Core PHP extensions', extension_loaded('pdo_mysql') && extension_loaded('fileinfo') ? 'PASS' : 'FAIL', 'pdo_mysql='.(extension_loaded('pdo_mysql') ? 'available' : 'missing').'; fileinfo='.(extension_loaded('fileinfo') ? 'available' : 'missing').'; gd='.(extension_loaded('gd') ? 'available' : 'missing').'.');
        $this->add($checks, 'Runtime', 'Session and cache drivers', 'INFO', 'session='.config('session.driver').'; cache='.config('cache.default').'; limiter='.config('cache.limiter').'; queue='.config('queue.default').'.');

        $persistent = (bool) config('database.connections.mysql.options.'.\PDO::ATTR_PERSISTENT, false);
        $this->add($checks, 'Runtime', 'Persistent MySQL option', $persistent ? 'INFO' : 'WARN', $persistent ? 'PDO persistence is configured. Host behavior and worker reuse still need live measurement.' : 'PDO persistence is not enabled.');
    }

    private function scanDatabase(array &$checks): void
    {
        try {
            $started = hrtime(true);
            $result = DB::selectOne('SELECT 1 AS zemtab_scan_ok');
            $elapsed = round((hrtime(true) - $started) / 1_000_000, 1);
            $ok = (int) ($result->zemtab_scan_ok ?? 0) === 1;
            $this->add($checks, 'Database', 'MySQL connectivity', $ok ? 'PASS' : 'FAIL', $ok ? "SELECT 1 succeeded in {$elapsed} ms." : 'Probe returned an unexpected value.');

            $tables = collect(Schema::getTableListing())
                ->map(fn ($table) => trim((string) $table, '`'))
                ->map(fn ($table) => strtolower(str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table))
                ->values()->all();
            $expected = ['users', 'restaurants', 'categories', 'menu_items', 'restaurant_tables', 'orders', 'order_items', 'service_requests', 'subscriptions', 'payments', 'staff_profiles', 'guest_sessions'];
            $missing = array_values(array_diff($expected, $tables));
            $this->add($checks, 'Database', 'Core schema tables', $missing === [] ? 'PASS' : 'FAIL', $missing === [] ? count($expected).' expected core tables found; total tables='.count($tables).'.' : 'Missing expected tables: '.implode(', ', $missing).'.');

            try {
                $usage = DB::selectOne('SELECT COUNT(*) AS table_count, COALESCE(SUM(DATA_LENGTH + INDEX_LENGTH), 0) AS bytes_used, COALESCE(SUM(TABLE_ROWS), 0) AS estimated_rows FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()');
                $this->add($checks, 'Database usage', 'Current database footprint', 'INFO', 'Tables='.(int) ($usage->table_count ?? 0).'; estimated rows='.(int) ($usage->estimated_rows ?? 0).'; data+index bytes='.(int) ($usage->bytes_used ?? 0).'. InnoDB row count is approximate.');
            } catch (Throwable) {
                $this->add($checks, 'Database usage', 'Current database footprint', 'NOT AVAILABLE', 'Host did not expose database size metadata.');
            }

            try {
                $statusRows = DB::select("SHOW GLOBAL STATUS WHERE Variable_name IN ('Connections','Threads_connected','Max_used_connections','Aborted_connects')");
                $stats = [];
                foreach ($statusRows as $row) {
                    $name = strtolower((string) ($row->Variable_name ?? ''));
                    if ($name !== '') {
                        $value = $row->Value ?? null;
                        $stats[$name] = is_numeric($value) ? (int) $value : $value;
                    }
                }
                $this->add($checks, 'Database usage', 'MySQL server status counters', $stats === [] ? 'NOT AVAILABLE' : 'INFO', $stats === [] ? 'Host returned no global status counters.' : json_encode($stats, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
            } catch (Throwable) {
                $this->add($checks, 'Database usage', 'MySQL server status counters', 'NOT AVAILABLE', 'The hosting account does not expose global status counters.');
            }
        } catch (Throwable $exception) {
            $this->add($checks, 'Database', 'MySQL connectivity and schema', 'FAIL', 'Database probe failed: '.$this->safeException($exception));
            $this->add($checks, 'Database usage', 'MySQL server status counters', 'NOT AVAILABLE', 'Skipped because the database probe failed.');
        }
    }

    private function scanRoutes(array &$checks, array &$inventory): void
    {
        try {
            $routes = Route::getRoutes();
            $names = [];
            $brokenActions = [];
            $guardIssues = [];
            $routeRows = [];
            foreach ($routes as $route) {
                if (! $route instanceof RoutingRoute) {
                    continue;
                }
                $name = $route->getName();
                if ($name) {
                    $names[$name] = true;
                }
                $uses = $route->getAction('uses');
                $actionName = (string) $route->getActionName();
                if ($uses instanceof \Closure || $actionName === 'Closure' || str_contains($actionName, '{closure}')) {
                    // Valid closure routes include Laravel's generated health,
                    // readiness, storage and redirect endpoints.
                } elseif (is_array($uses) && count($uses) === 2) {
                    if (! is_callable($uses)) {
                        $brokenActions[] = $name ?: $route->uri();
                    }
                } elseif (is_string($uses) && in_array($uses, ['Closure', 'Illuminate\\Routing\\RouteAction'], true)) {
                    // Cached Laravel routes may serialize closure actions as a
                    // string. They are valid actions, not unresolved classes.
                } elseif (is_string($uses) && str_contains($uses, '@')) {
                    [$class, $method] = explode('@', $uses, 2);
                    if (! class_exists($class) || ! method_exists($class, $method)) {
                        $brokenActions[] = $name ?: $route->uri();
                    }
                } elseif (! is_callable($uses)) {
                    $brokenActions[] = $name ?: $route->uri();
                }

                $middleware = $route->gatherMiddleware();
                if (str_starts_with((string) $name, 'admin.') && (! in_array('auth', $middleware, true) || ! in_array('role:admin', $middleware, true))) {
                    $guardIssues[] = $name ?: $route->uri();
                } elseif (str_starts_with((string) $name, 'restaurant.') && $name !== 'restaurant.orders.poll'
                    && (! in_array('auth', $middleware, true) || ! in_array('role:restaurant_owner,staff', $middleware, true))) {
                    $guardIssues[] = $name ?: $route->uri();
                } elseif ($name === 'restaurant.orders.poll' && ! in_array('signed', $middleware, true)) {
                    $guardIssues[] = $name;
                }

                $methods = $route->methods();
                $requiresQuery = $name === 'admin.database.workboard-reset.preview';
                $routeRows[] = [
                    'name' => $name ?: '(unnamed)',
                    'method' => implode('|', $methods),
                    'uri' => $route->uri(),
                    // The result page may safely smoke-test parameterless GET
                    // routes in the already-authenticated admin browser. The
                    // reset preview also needs a restaurant_id query value, so
                    // leave it for its dedicated workflow instead of probing a
                    // guaranteed validation/method error.
                    'browser_url' => in_array('GET', $methods, true) && ! str_contains($route->uri(), '{') && ! $requiresQuery
                        ? url($route->uri()) : null,
                    'browser_skip_reason' => $requiresQuery ? 'Requires restaurant_id query parameter.' : null,
                ];
            }
            $inventory['routes'] = $routeRows;
            $this->add($checks, 'Routes', 'Registered route actions', $brokenActions === [] ? 'PASS' : 'FAIL', count($routeRows).' routes inventoried; unresolved actions='.count($brokenActions).($brokenActions === [] ? '.' : ': '.implode(', ', $brokenActions)));
            $this->add($checks, 'Security', 'Admin and restaurant route guards', $guardIssues === [] ? 'PASS' : 'FAIL', $guardIssues === [] ? 'Expected authentication/role guards are present; signed polling is checked separately.' : 'Guard issues: '.implode(', ', $guardIssues).'.');

            $missingNames = [];
            foreach ($this->bladeFiles() as $file) {
                $source = file_get_contents($file);
                if (! is_string($source)) {
                    continue;
                }
                preg_match_all('/\broute\(\s*[\'\"]([^\'\"]+)[\'\"]/', $source, $matches);
                foreach ($matches[1] ?? [] as $routeName) {
                    if (! isset($names[$routeName])) {
                        $missingNames[$routeName] = true;
                    }
                }
            }
            $this->add($checks, 'Routes', 'Blade route references', $missingNames === [] ? 'PASS' : 'FAIL', $missingNames === [] ? 'All statically named Blade route() references resolve.' : 'Missing route names: '.implode(', ', array_keys($missingNames)).'.');
        } catch (Throwable $exception) {
            $this->add($checks, 'Routes', 'Route and authorization inventory', 'FAIL', 'Route scan failed: '.$this->safeException($exception));
        }
    }

    private function scanViews(array &$checks, array &$inventory): void
    {
        $files = $this->bladeFiles();
        $compiler = app('blade.compiler');
        $syntaxErrors = [];
        $counts = ['forms' => 0, 'buttons' => 0, 'links' => 0, 'inputs' => 0];
        $byView = [];

        foreach ($files as $file) {
            $source = file_get_contents($file);
            if (! is_string($source)) {
                $syntaxErrors[] = basename($file).': unreadable';
                continue;
            }
            try {
                token_get_all($compiler->compileString($source), TOKEN_PARSE);
            } catch (Throwable $exception) {
                $syntaxErrors[] = basename($file).': '.$this->safeException($exception);
            }
            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file);
            $row = [
                'view' => $relative,
                'forms' => preg_match_all('/<form\b/i', $source),
                'buttons' => preg_match_all('/<button\b/i', $source),
                'links' => preg_match_all('/<a\b/i', $source),
                'inputs' => preg_match_all('/<(?:input|select|textarea)\b/i', $source),
            ];
            foreach ($counts as $key => $value) {
                $counts[$key] += $row[$key];
            }
            $byView[] = $row;
        }
        $inventory['views'] = $byView;
        $inventory['interactive_control_counts'] = $counts;
        $this->add($checks, 'Views', 'Blade template compilation', $syntaxErrors === [] ? 'PASS' : 'FAIL', count($files).' templates scanned; syntax errors='.count($syntaxErrors).($syntaxErrors === [] ? '.' : ': '.implode(' | ', $syntaxErrors)));
        $this->add($checks, 'UI inventory', 'Forms, buttons, links, and fields', 'INFO', count($files).' views inventoried: '.json_encode($counts).'. This is a source inventory, not proof that browser interactions work.');
    }

    private function scanAssetsAndStorage(array &$checks, array &$inventory): void
    {
        $requiredAssets = ['assets/app.css', 'assets/alpine.min.js', 'assets/qr-editor.js', 'logo/zemtab-pantone-1795-c-icon-text-transparent.png'];
        $staticReferences = [];
        foreach ($this->bladeFiles() as $file) {
            $source = file_get_contents($file);
            if (is_string($source)) {
                preg_match_all('/\basset\(\s*[\'\"]([^\'\"]+)[\'\"]/', $source, $matches);
                foreach ($matches[1] ?? [] as $asset) {
                    // Ignore concatenated prefixes such as asset('storage/'.$path).
                    if (! str_contains($asset, '/') || str_ends_with($asset, '/') || str_ends_with($asset, '.')) {
                        continue;
                    }
                    if (! preg_match('/^(?:https?:|data:|\/\/)/i', $asset)) {
                        $staticReferences[$asset] = true;
                    }
                }
            }
        }
        $allAssets = array_values(array_unique([...$requiredAssets, ...array_keys($staticReferences)]));
        $missing = [];
        $assetRows = [];
        foreach ($allAssets as $asset) {
            $path = public_path($asset);
            $exists = is_file($path);
            $size = $exists ? filesize($path) : false;
            $assetRows[] = ['path' => $asset, 'exists' => $exists, 'bytes' => $size === false ? null : $size];
            if (! $exists) {
                $missing[] = $asset;
            }
        }
        $inventory['required_assets'] = $assetRows;
        $this->add($checks, 'Assets', 'Required and statically referenced local assets', $missing === [] ? 'PASS' : 'FAIL', count($allAssets).' distinct asset paths checked; missing='.count($missing).($missing === [] ? '.' : ': '.implode(', ', $missing).'.'));

        $directories = [
            'storage/logs' => storage_path('logs'),
            'storage/framework/views' => storage_path('framework/views'),
            'storage/framework/cache/data' => storage_path('framework/cache/data'),
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'public/uploads' => public_path('uploads'),
        ];
        $directoryRows = [];
        $problems = [];
        foreach ($directories as $label => $path) {
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            $directoryRows[] = ['path' => $label, 'exists' => $exists, 'writable' => $writable];
            if (! $exists || ! $writable) {
                $problems[] = $label.(!$exists ? ' missing' : ' not writable');
            }
        }
        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());
        $inventory['storage_directories'] = $directoryRows;
        $inventory['storage_free_bytes'] = $free === false ? null : (int) $free;
        $inventory['storage_total_bytes'] = $total === false ? null : (int) $total;
        $this->add($checks, 'Storage', 'Writable runtime directories', $problems === [] ? 'PASS' : 'FAIL', $problems === [] ? 'Required runtime directories exist and are writable.' : implode('; ', $problems).'.');
        $this->add($checks, 'Storage usage', 'Disk space', $free === false ? 'NOT AVAILABLE' : ($free < 512 * 1024 * 1024 ? 'WARN' : 'INFO'), $free === false ? 'Host does not expose free disk space.' : round($free / 1024 / 1024, 1).' MiB free of '.($total === false ? 'unknown total' : round($total / 1024 / 1024, 1).' MiB total; host-volume-wide.'));
    }

    private function scanRecentLogs(array &$checks): void
    {
        $path = storage_path('logs/laravel.log');
        if (! is_file($path) || ! is_readable($path)) {
            $this->add($checks, 'Recent errors', 'Laravel error log summary', 'NOT AVAILABLE', 'Default Laravel log is missing or unreadable.');
            return;
        }
        $handle = fopen($path, 'rb');
        if (! $handle) {
            $this->add($checks, 'Recent errors', 'Laravel error log summary', 'NOT AVAILABLE', 'Default Laravel log could not be opened.');
            return;
        }
        $size = filesize($path) ?: 0;
        fseek($handle, max(0, $size - 2 * 1024 * 1024));
        $tail = stream_get_contents($handle);
        fclose($handle);
        $cutoff = now()->subHours(24)->getTimestamp();
        $recentErrors = 0;
        $latest = null;
        foreach (preg_split('/\R/', is_string($tail) ? $tail : '') as $line) {
            if (! preg_match('/^\[(\d{4}-\d\d-\d\d \d\d:\d\d:\d\d)\].*\.ERROR:/', $line, $match)) {
                continue;
            }
            $timestamp = strtotime($match[1]);
            if ($timestamp !== false && $timestamp >= $cutoff) {
                $recentErrors++;
                $latest = $match[1];
            }
        }
        $this->add($checks, 'Recent errors', 'Laravel error log summary (last 24h)', $recentErrors === 0 ? 'PASS' : 'WARN', $recentErrors.' error entries in the last 24 hours'.($latest ? '; latest at '.$latest : '').'. Error contents are omitted.');
    }

    private function bladeFiles(): array
    {
        $files = [];
        $root = resource_path('views');
        if (! is_dir($root)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }

    private function add(array &$checks, string $group, string $name, string $status, string $detail): void
    {
        $checks[] = compact('group', 'name', 'status', 'detail');
    }

    private function safeException(Throwable $exception): string
    {
        return class_basename($exception).' (code '.(string) $exception->getCode().')';
    }

    private function iniBytes(string $value): ?int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return $value === '-1' ? -1 : null;
        }
        $unit = strtolower(substr($value, -1));
        $number = (float) $value;
        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
