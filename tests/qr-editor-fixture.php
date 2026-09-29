<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$restaurant = new App\Models\Restaurant(['name'=>'Test venue','slug'=>'test','logo_path'=>'logo/zemtab-pantone-1795-c-icon-text-transparent.png']);
$table = new App\Models\RestaurantTable(['table_number'=>'204','location_type'=>'room']);
$table->setRelation('restaurant',$restaurant);
$controller = new App\Http\Controllers\Restaurant\TableController;
$method = new ReflectionMethod($controller,'defaultStickerSettings');
$sticker = $method->invoke($controller,$restaurant);
if (isset($argv[1])) {
    $sticker['elements'] = json_decode($argv[1],true,512,JSON_THROW_ON_ERROR);
}
$previewQr = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100"/></svg>';
$logoUrl = asset('storage/'.$restaurant->logo_path);
$qrCardDesigns = [];
foreach (['table' => 'SCAN TO ORDER', 'room' => 'SCAN FOR ROOM SERVICE'] as $type => $scanText) {
    $orientations = [];
    foreach (['portrait', 'landscape'] as $orientation) {
        $orientations[$orientation] = array_merge($sticker, [
            'orientation' => $orientation,
            'scan_text' => $scanText,
            'logo_url' => $logoUrl,
            'restaurant_logo_url' => $logoUrl,
        ]);
    }
    $qrCardDesigns[$type] = ['preferred_orientation' => 'portrait', 'orientations' => $orientations];
}
echo view('restaurant.tables.design-settings',[
 'restaurant'=>$restaurant,'sticker'=>$sticker,'previewTable'=>$table,
 'previewCards'=>[
   'table'=>['qr'=>$previewQr,'label'=>'Table 1'],
   'room'=>['qr'=>$previewQr,'label'=>'Room 204'],
 ],
 'qrCardDesigns'=>$qrCardDesigns,'designType'=>'table','designOrientation'=>'portrait',
 'errors'=>new Illuminate\Support\ViewErrorBag
])->render();
