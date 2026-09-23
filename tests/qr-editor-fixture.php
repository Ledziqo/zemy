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
echo view('restaurant.tables.design-settings',[
 'restaurant'=>$restaurant,'sticker'=>$sticker,'previewTable'=>$table,
 'previewQr'=>'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100"/></svg>',
 'errors'=>new Illuminate\Support\ViewErrorBag
])->render();
