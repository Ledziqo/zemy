<?php

return [
    'github_repository' => env('RELEASE_TEST_GITHUB_REPOSITORY', 'Ledziqo/zemy'),
    'github_token' => env('ZEMTAB_GITHUB_TOKEN', env('RELEASE_TEST_GITHUB_TOKEN')),
    'callback_secret' => env('ZEMTAB_CALLBACK_SECRET', env('RELEASE_TEST_CALLBACK_SECRET')),
    'event' => env('RELEASE_TEST_GITHUB_EVENT', 'zemtab-complete-release-test'),
    'ttl_minutes' => (int) env('RELEASE_TEST_TTL_MINUTES', 360),
];
