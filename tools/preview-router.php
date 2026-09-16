<?php
declare(strict_types=1);

// Start only with: php -d extension=pdo_sqlite -S 127.0.0.1:8091 -t public tools/preview-router.php
if (PHP_SAPI !== 'cli-server' || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    || !in_array($_SERVER['HTTP_HOST'] ?? '', ['127.0.0.1:8091', 'localhost:8091', '[::1]:8091'], true)) {
    http_response_code(403); exit('Loopback preview only');
}
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
// Serve only public static assets; never execute alternate PHP entrypoints or storage links.
$public = realpath(__DIR__.'/../public');
$file = realpath($public.$path);
if ($file && is_file($file) && str_starts_with($file, $public.DIRECTORY_SEPARATOR)
    && preg_match('/\.(css|js|png|jpg|jpeg|svg|webp|ico|woff2?|ttf|mp3|wav)$/i', $file)) return false;
if (str_starts_with($path, '/setup') || str_starts_with($path, '/admin/setup') || str_contains($path, '.php')) {
    http_response_code(403); exit('Setup and PHP entrypoints disabled in preview');
}
require __DIR__.'/launch-fixture.php';
$app = launchApp();
if (!is_file(config('database.connections.sqlite.database'))) { http_response_code(503); exit('Run tools/launch-check.php --setup-preview first'); }
// Keep CSRF enabled for browser requests even though the isolated application is testing.
$app->instance('env', 'local');
$app->handleRequest(\Illuminate\Http\Request::capture());
