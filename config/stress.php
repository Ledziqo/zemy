<?php

return [
    'allow_production' => (bool) env('STRESS_TEST_ALLOW_PRODUCTION', false),
    'batch_size' => 10,
    'max_restaurants' => 150,
];
