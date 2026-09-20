<?php

// Render an isolated fixture: no database reads or writes.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$restaurant = new App\Models\Restaurant(['name'=>'Preview venue', 'slug'=>'preview', 'primary_color'=>'#D22630', 'settings'=>[]]);
$restaurant->logo_path = 'http://localhost/logo/zemtab-pantone-1795-c-icon-text-transparent.png';
$controller = new App\Http\Controllers\Restaurant\TableController();
$defaults = new ReflectionMethod($controller, 'defaultStickerSettings');
$sticker = $defaults->invoke($controller, $restaurant);
$build = new ReflectionMethod($controller, 'buildQr');
$tables = collect();
$qrImages = [];
for ($i=1; $i<=13; $i++) {
    $table = new App\Models\RestaurantTable(['table_number'=>(string)$i, 'location_type'=>$i % 2 ? 'table' : 'room']);
    $table->id = $i;
    $table->setRelation('restaurant', $restaurant);
    $tables->push($table);
    $qrImages[$i] = 'data:image/svg+xml;base64,'.base64_encode($build->invoke($controller,$restaurant,$table)->getString());
}
if (in_array('--max', $argv)) {
    $sticker = array_merge($sticker, ['logo_size'=>30,'text_size'=>22,'qr_size'=>50,'detail_size'=>9,'room_scan_text'=>str_repeat('LONG ',8)]);
}
$html = view('restaurant.tables.setup_pack', compact('restaurant','tables','sticker','qrImages'))->render();
if (substr_count($html, '<section class="qr-page">') !== 2 || substr_count($html, '<article class="signature-card"') !== 13) {
    throw new RuntimeException('Incorrect pagination or card count');
}
if (in_array('--studio', $argv)) {
    $previewTable = $tables->first();
    $previewQr = $qrImages[1];
    $errors = new Illuminate\Support\ViewErrorBag();
    $html = '<!doctype html><html><body>'.view('restaurant.tables.design-settings', compact('restaurant','sticker','previewTable','previewQr','errors'))->render().'</body></html>';
}
$logo = 'data:image/png;base64,'.base64_encode(file_get_contents(__DIR__.'/../public/logo/zemtab-pantone-1795-c-icon-text-transparent.png'));
$html = preg_replace('~(?:http://localhost|https?://[^" ]+)/logo/zemtab-pantone-1795-c-icon-text-transparent.png~', $logo, $html);
echo $html;
