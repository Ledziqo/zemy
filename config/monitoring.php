<?php

return [
    'database' => [
        // Hostinger's observed account quota; override per hosting plan if it changes.
        'connections_per_hour_limit' => (int) env('DB_CONNECTIONS_PER_HOUR_LIMIT', 500),
        // Approximate plan quota. MySQL information_schema size can differ from provider billing.
        'size_limit_bytes' => (int) env('DB_SIZE_LIMIT_BYTES', 3_000_000_000),
    ],
];
