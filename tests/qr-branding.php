<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$restaurant = new App\Models\Restaurant(['name' => 'Test venue', 'slug' => 'test']);
$table = new App\Models\RestaurantTable(['table_number' => '204', 'location_type' => 'room']);
$controller = new App\Http\Controllers\Restaurant\TableController();
$builder = new ReflectionMethod($controller, 'buildQr');
$svg = $builder->invoke($controller, $restaurant, $table);
$xml = simplexml_load_string($svg);

$check = static function (bool $condition, string $message): void {
    if (! $condition) {
        throw new RuntimeException($message);
    }
};

$check($xml instanceof SimpleXMLElement, 'The QR code is valid SVG.');
$images = $xml->xpath('//*[local-name()="image"]') ?: [];
$rectangles = $xml->xpath('//*[local-name()="rect"]') ?: [];
$check(count($images) === 1, 'The generated QR contains one embedded brand mark.');
$check(count($rectangles) === 2, 'The QR retains its background and adds a logo knockout.');
$href = (string) ($images[0]['href'] ?? '');
$check(str_starts_with($href, 'data:image/png;base64,'), 'The logo is embedded and does not depend on an external image URL.');
$embeddedLogo = base64_decode(substr($href, strlen('data:image/png;base64,')), true);
$check($embeddedLogo === file_get_contents(public_path('logo/zemtab-pantone-1795-c-icon-transparent.png')), 'The embedded mark is the official ZemTab icon.');
$size = (float) $xml['width'];
$logoX = (float) $images[0]['x'];
$logoY = (float) $images[0]['y'];
$logoWidth = (float) $images[0]['width'];
$logoHeight = (float) $images[0]['height'];
$check(abs(($logoX + $logoWidth / 2) - $size / 2) < 0.01 && abs(($logoY + $logoHeight / 2) - $size / 2) < 0.01, 'The logo is centered over the QR matrix.');

echo "Passed 6 QR branding checks; generated inline SVG uses no database or image-processing extension.\n";
