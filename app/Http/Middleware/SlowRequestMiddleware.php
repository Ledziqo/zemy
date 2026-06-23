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
        $start = microtime(true);
        $queryLogEnabled = config('app.debug') || env('SLOW_QUERY_LOG', true);

        if ($queryLogEnabled) {
            DB::enableQueryLog();
        }

        $response = $next($request);

        $duration = microtime(true) - $start;
        $durationMs = round($duration * 1000);

        // Log requests slower than 500ms
        if ($durationMs > (int) env('SLOW_REQUEST_THRESHOLD_MS', 500)) {
            $queries = DB::getQueryLog();
            $queryCount = count($queries);
            $slowQueries = array_filter($queries, fn ($q) => $q['time'] > (float) env('SLOW_QUERY_THRESHOLD_MS', 100));

            $logData = [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => $durationMs,
                'query_count' => $queryCount,
                'status' => $response->getStatusCode(),
                'ip' => $request->ip(),
            ];

            if (!empty($slowQueries)) {
                $logData['slow_queries'] = array_map(fn ($q) => [
                    'sql' => $q['query'],
                    'bindings' => $q['bindings'],
                    'time_ms' => $q['time'],
                ], array_slice($slowQueries, 0, 5));
            }

            Log::channel('slow')->warning('Slow request detected', $logData);
        }

        return $response;
    }
}