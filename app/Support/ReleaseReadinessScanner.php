<?php

namespace App\Support;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

/** Runs bounded, read-only release diagnostics; never creates app data or load. */
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
        $this->add($checks, 'Coverage', 'Mutating end-to-end workflows', 'NOT RUN', 'This read-only scan does not create orders, upload files, change settings, replace menus, delete records, or exercise browser clicks. Those require a separate synthetic-data test.');
        $this->add($checks, 'Capacity', 'Concurrent hotel load test', 'NOT RUN', 'This button does not generate production traffic. Run the corrected external load-test harness separately with staged, disposable tenants.');
        $this->add($checks, 'Hosting limits', 'Exact per-account hourly MySQL connection quota', 'NOT AVAILABLE', 'Shared hosting may not expose this quota. Any global MySQL counters are server-wide, not ZemTab-only.');

        $statuses = array_column($checks, 'status');
        $overall = in_array('FAIL', $statuses, true)
            ? 'NO-GO'
            : (count(array_intersect($statuses, ['WARN', 'NOT RUN', 'NOT AVAILABLE'])) > 0 ? 'REVIEW REQUIRED' : 'PASS');

        return [
            'report' => 'ZemTab read-only release scan',
            'generated_at' => now()->toIso8601String(),
            'application' => ['environment' => app()->environment(), 'laravel' => app()->version(), 'php' => PHP_VERSION],
            'overall' => $overall,
            'duration_ms' => round((hrtime(true) - $started) / 1_000_000, 1),
            'database_work' => ['diagnostic_queries' => $sqlQueries, 'query_time_ms' => round($sqlMilliseconds, 1)],
            'inventory' => $inventory,
            'checks' => $checks,
            'important' => 'A PASS means only the listed read-only checks passed. This is not complete functional or capacity certification.',
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

            $tables = Schema::getTableListing();
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
                if ($uses instanceof \Closure) {
                    // Valid closure route.
                } elseif (is_array($uses) && count($uses) === 2) {
                    if (! is_callable($uses)) {
                        $brokenActions[] = $name ?: $route->uri();
                    }
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

                $routeRows[] = ['name' => $name ?: '(unnamed)', 'method' => implode('|', $route->methods()), 'uri' => $route->uri()];
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
