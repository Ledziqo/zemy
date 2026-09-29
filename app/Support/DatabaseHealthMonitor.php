<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseHealthMonitor
{
    private const SAMPLE_INTERVAL_SECONDS = 300;
    private const HISTORY_SECONDS = 3900;

    public function snapshot(): array
    {
        $cache = Cache::store('file');
        $connectionKey = sha1(implode('|', [
            (string) config('database.connections.mysql.host'),
            (string) config('database.connections.mysql.database'),
            (string) config('database.connections.mysql.username'),
        ]));
        $snapshotKey = 'admin_database_health_snapshot_'.$connectionKey;

        if (($snapshot = $cache->get($snapshotKey)) !== null) {
            return $snapshot;
        }

        try {
            return $cache->lock($snapshotKey.'_lock', 20)->block(3, function () use ($cache, $snapshotKey, $connectionKey) {
                if (($snapshot = $cache->get($snapshotKey)) !== null) {
                    return $snapshot;
                }

                $snapshot = $this->collect($connectionKey);
                $cache->put($snapshotKey, $snapshot, now()->addSeconds(self::SAMPLE_INTERVAL_SECONDS));
                return $snapshot;
            });
        } catch (Throwable) {
            return [
                'available' => false,
                'sampled_at' => now()->toIso8601String(),
                'message' => 'Could not sample database health right now. Try again shortly.',
            ];
        }
    }

    /** Pure helper so rolling-window and counter-reset behavior can be tested without MySQL. */
    public static function connectionWindow(array $samples, int $now): array
    {
        $cutoff = $now - 3600;
        $samples = array_values(array_filter($samples, static fn ($sample) =>
            is_array($sample)
            && isset($sample['at'], $sample['connections'])
            && (int) $sample['at'] <= $now
            && (int) $sample['at'] >= $now - self::HISTORY_SECONDS
        ));

        if (count($samples) < 2) {
            return ['count' => null, 'duration_seconds' => 0, 'full_hour' => false];
        }

        $last = $samples[array_key_last($samples)];
        $first = null;
        foreach ($samples as $sample) {
            if ((int) $sample['at'] >= $cutoff) {
                $first = $sample;
                break;
            }
        }

        if ($first === null) {
            $first = $samples[0];
        }

        $duration = max(0, (int) $last['at'] - (int) $first['at']);
        $difference = (int) $last['connections'] - (int) $first['connections'];
        if ($difference < 0) {
            return ['count' => null, 'duration_seconds' => 0, 'full_hour' => false];
        }

        return [
            'count' => $difference,
            'duration_seconds' => $duration,
            'full_hour' => $duration >= 3500,
        ];
    }

    private function collect(string $connectionKey): array
    {
        $sampledAt = now();
        try {
            $accountStats = $this->accountConnectionStats();
            $globalStats = $this->globalStatus();
            $accountScoped = $accountStats !== null;
            $counter = $accountScoped
                ? $accountStats['total_connections']
                : ($globalStats['connections'] ?? null);
            $scope = $accountScoped ? 'database_account' : 'mysql_server';
            $window = $counter === null
                ? ['count' => null, 'duration_seconds' => 0, 'full_hour' => false]
                : $this->recordSample($connectionKey, $scope, (int) $counter, $sampledAt->timestamp);

            $size = null;
            try {
                $size = DB::selectOne(
                    'SELECT COUNT(*) AS table_count, '
                    .'COALESCE(SUM(DATA_LENGTH + INDEX_LENGTH), 0) AS bytes_used, '
                    .'COALESCE(SUM(TABLE_ROWS), 0) AS estimated_rows '
                    .'FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()'
                );
            } catch (Throwable) {
                // Some shared-hosting accounts hide schema-size metadata.
            }

            $connectionLimit = max(0, (int) config('monitoring.database.connections_per_hour_limit', 0));
            $sizeLimit = max(0, (int) config('monitoring.database.size_limit_bytes', 0));
            $databaseBytes = isset($size->bytes_used) ? (int) $size->bytes_used : null;
            $connectionCount = $window['count'];
            $connectionPercent = $accountScoped && $window['full_hour'] && $connectionLimit > 0 && $connectionCount !== null
                ? round(($connectionCount / $connectionLimit) * 100, 1)
                : null;
            $sizePercent = $databaseBytes !== null && $sizeLimit > 0
                ? round(($databaseBytes / $sizeLimit) * 100, 1)
                : null;
            $currentConnections = $accountScoped
                ? $accountStats['current_connections']
                : ($globalStats['threads_connected'] ?? null);
            $maxConnections = $globalStats['max_connections'] ?? null;
            $activePercent = $currentConnections !== null && $maxConnections
                ? round(($currentConnections / $maxConnections) * 100, 1)
                : null;

            $alerts = [];
            if ($connectionPercent !== null && $connectionPercent >= 90) {
                $alerts[] = ['level' => 'critical', 'text' => 'Database-account connections are at least 90% of the configured hourly limit.'];
            } elseif ($connectionPercent !== null && $connectionPercent >= 70) {
                $alerts[] = ['level' => 'warning', 'text' => 'Database-account connections have passed 70% of the configured hourly limit.'];
            }
            if ($sizePercent !== null && $sizePercent >= 90) {
                $alerts[] = ['level' => 'critical', 'text' => 'Estimated database size is at least 90% of the configured plan limit.'];
            } elseif ($sizePercent !== null && $sizePercent >= 75) {
                $alerts[] = ['level' => 'warning', 'text' => 'Estimated database size has passed 75% of the configured plan limit.'];
            }
            if ($activePercent !== null && $activePercent >= 85) {
                $alerts[] = ['level' => 'warning', 'text' => 'Current MySQL server connections are near the server-wide connection ceiling.'];
            }

            return [
                'available' => true,
                'sampled_at' => $sampledAt->toIso8601String(),
                'scope' => $scope,
                'connection_count' => $connectionCount,
                'connection_window_seconds' => $window['duration_seconds'],
                'connection_window_full_hour' => $window['full_hour'],
                'connection_limit' => $connectionLimit ?: null,
                'connection_percent' => $connectionPercent,
                'current_connections' => $currentConnections,
                'max_used_connections' => $globalStats['max_used_connections'] ?? null,
                'max_connections' => $maxConnections,
                'aborted_connects' => $globalStats['aborted_connects'] ?? null,
                'database_bytes' => $databaseBytes,
                'database_size_limit_bytes' => $sizeLimit ?: null,
                'database_size_percent' => $sizePercent,
                'table_count' => isset($size->table_count) ? (int) $size->table_count : null,
                'estimated_rows' => isset($size->estimated_rows) ? (int) $size->estimated_rows : null,
                'alerts' => $alerts,
                'notice' => $accountScoped
                    ? 'Account-level connection counter found; hourly total is derived from five-minute samples.'
                    : 'Host exposes only server-wide connection counters. This hourly estimate includes other users and cannot be compared to ZemTab’s account quota.',
            ];
        } catch (Throwable) {
            return [
                'available' => false,
                'sampled_at' => $sampledAt->toIso8601String(),
                'message' => 'The hosting account did not allow the database health counters to be read.',
            ];
        }
    }

    private function accountConnectionStats(): ?array
    {
        $user = DB::selectOne("SELECT SUBSTRING_INDEX(CURRENT_USER(), '@', 1) AS db_user");
        $username = (string) ($user->db_user ?? '');
        if ($username === '') {
            return null;
        }

        try {
            $stats = DB::selectOne(
                'SELECT COALESCE(SUM(TOTAL_CONNECTIONS), 0) AS total_connections, '
                .'COALESCE(SUM(CURRENT_CONNECTIONS), 0) AS current_connections '
                .'FROM performance_schema.accounts WHERE USER = ?',
                [$username]
            );
            if ($stats !== null) {
                return [
                    'total_connections' => (int) $stats->total_connections,
                    'current_connections' => (int) $stats->current_connections,
                ];
            }
        } catch (Throwable) {
            // Try MariaDB's optional per-user statistics table next.
        }

        try {
            $stats = DB::selectOne(
                'SELECT COALESCE(SUM(TOTAL_CONNECTIONS), 0) AS total_connections, '
                .'COALESCE(SUM(CONCURRENT_CONNECTIONS), 0) AS current_connections '
                .'FROM information_schema.USER_STATISTICS WHERE USER = ?',
                [$username]
            );
            if ($stats !== null) {
                return [
                    'total_connections' => (int) $stats->total_connections,
                    'current_connections' => (int) $stats->current_connections,
                ];
            }
        } catch (Throwable) {
            // Shared-hosting plans commonly deny these optional account views.
        }

        return null;
    }

    private function globalStatus(): array
    {
        $status = [];
        try {
            foreach (DB::select("SHOW GLOBAL STATUS WHERE Variable_name IN ('Connections','Threads_connected','Max_used_connections','Aborted_connects')") as $row) {
                $name = strtolower((string) ($row->Variable_name ?? ''));
                if (isset($row->Value) && is_numeric($row->Value)) {
                    $status[$name] = (int) $row->Value;
                }
            }
        } catch (Throwable) {
            // Database-account counters and size can still be useful without global privileges.
        }
        try {
            $variables = DB::select("SHOW GLOBAL VARIABLES WHERE Variable_name = 'max_connections'");
            if (isset($variables[0]->Value) && is_numeric($variables[0]->Value)) {
                $status['max_connections'] = (int) $variables[0]->Value;
            }
        } catch (Throwable) {
            // This ceiling is supplementary; account-level stats may still work.
        }
        return $status;
    }

    private function recordSample(string $connectionKey, string $scope, int $counter, int $at): array
    {
        $cache = Cache::store('file');
        $key = 'admin_database_health_connections_'.sha1($connectionKey.'|'.$scope);
        $samples = (array) $cache->get($key, []);
        $samples = array_values(array_filter($samples, static fn ($sample) =>
            is_array($sample) && (int) ($sample['at'] ?? 0) >= $at - self::HISTORY_SECONDS
        ));

        $last = $samples === [] ? null : $samples[array_key_last($samples)];
        if ($last !== null && $counter < (int) $last['connections']) {
            $samples = [];
            $last = null;
        }
        if ($last === null || $at - (int) $last['at'] >= self::SAMPLE_INTERVAL_SECONDS) {
            $samples[] = ['at' => $at, 'connections' => $counter];
        }

        $cache->put($key, $samples, now()->addHours(2));
        return self::connectionWindow($samples, $at);
    }
}
