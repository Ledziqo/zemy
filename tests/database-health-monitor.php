<?php

require __DIR__.'/../app/Support/DatabaseHealthMonitor.php';

use App\Support\DatabaseHealthMonitor;

$now = 1_800_000_000;
$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
    if (! $condition) {
        throw new RuntimeException($message);
    }
    $checks++;
};

$empty = DatabaseHealthMonitor::connectionWindow([], $now);
$check($empty['count'] === null && ! $empty['full_hour'], 'A baseline is required before reporting an hourly count.');

$warming = DatabaseHealthMonitor::connectionWindow([
    ['at' => $now - 300, 'connections' => 100],
    ['at' => $now, 'connections' => 108],
], $now);
$check($warming['count'] === 8 && $warming['duration_seconds'] === 300 && ! $warming['full_hour'], 'Partial baseline must report its measured interval.');

$hour = DatabaseHealthMonitor::connectionWindow([
    ['at' => $now - 3600, 'connections' => 100],
    ['at' => $now - 1800, 'connections' => 112],
    ['at' => $now, 'connections' => 127],
], $now);
$check($hour['count'] === 27 && $hour['full_hour'], 'A full rolling hour must subtract the oldest in-window counter.');

$reset = DatabaseHealthMonitor::connectionWindow([
    ['at' => $now - 3600, 'connections' => 100],
    ['at' => $now, 'connections' => 4],
], $now);
$check($reset['count'] === null && ! $reset['full_hour'], 'A server counter reset must restart the baseline, not report a negative delta.');

echo "Passed $checks database-health rolling-window checks; no database access.\n";
