<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SlowRequestMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only log queries when explicitly enabled — query logging is expensive on production
        $queryLogEnabled = env('SLOW_QUERY_LOG', false);

        $start = microtime(true);

        if ($queryLogEnabled) {
            DB::enableQueryLog();
        }

        $response = $next($request);

        $duration = microtime(true) - $start;
        $durationMs = round($duration * 1000);

        // Log requests slower than threshold
        if ($durationMs > (int) env('SLOW_REQUEST_THRESHOLD_MS', 500)) {
            $logData = [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => $durationMs,
                'status' => $response->getStatusCode(),
                'ip' => $request->ip(),
            ];

            if ($queryLogEnabled) {
                $queries = DB::getQueryLog();
                $logData['query_count'] = count($queries);
                $slowQueries = array_filter($queries, fn ($q) => $q['time'] > (float) env('SLOW_QUERY_THRESHOLD_MS', 100));
                if (!empty($slowQueries)) {
                    $logData['slow_queries'] = array_map(fn ($q) => [
                        'sql' => $q['query'],
                        'time_ms' => $q['time'],
                    ], array_slice($slowQueries, 0, 5));
                }
            }

            Log::channel('slow')->warning('Slow request detected', $logData);
        }

        return $response;
    }
}