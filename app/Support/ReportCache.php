<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class ReportCache
{
    public static function key(string $report, int $restaurantId, string $revision, array $parameters = []): string
    {
        return 'restaurant_report:'.$report.':'.$restaurantId.':'.sha1($revision.'|'.json_encode($parameters));
    }

    public static function remember(string $report, int $restaurantId, string $revision, array $parameters, \Closure $callback, int $seconds = 20): mixed
    {
        return Cache::store('file')->remember(
            self::key($report, $restaurantId, $revision, $parameters),
            now()->addSeconds($seconds),
            $callback
        );
    }
}
