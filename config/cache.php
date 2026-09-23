<?php

return [
    'default' => env('CACHE_STORE', 'file'),
    // Laravel's throttle middleware must not silently create DB-backed cache
    // traffic on every menu/order/poll request if CACHE_STORE is later changed.
    'limiter' => 'file',
    'stores' => [
        'array' => ['driver' => 'array', 'serialize' => false],
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => env('DB_CACHE_LOCK_TABLE', 'cache_locks'),
        ],
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],
    ],
    'prefix' => env('CACHE_PREFIX', 'zemtab_cache_'),
];
