<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// In-memory model verifies the real save action without changing a database.
$restaurant = new class extends App\Models\Restaurant {
    public function update(array $attributes = [], array $options = [])
    {
        $this->fill($attributes);
        return true;
    }
};
$restaurant->settings = ['unrelated'=>'preserved', 'qr_sticker'=>['legacy'=>'preserved']];
$user = new App\Models\User();
$user->setRelation('restaurant', $restaurant);
$controller = new App\Http\Controllers\Restaurant\TableController();
$data = ['background_color'=>'#FFFFFF','text_color'=>'#171717','accent_color'=>'#D22630','border_color'=>'#CCCCCC','logo_size'=>24,'text_size'=>18,'qr_size'=>46,'detail_size'=>7,'art_opacity'=>80,'table_scan_text'=>'SCAN TO ORDER','room_scan_text'=>'SCAN FOR ROOM SERVICE'];
$request = Illuminate\Http\Request::create('/restaurant/tables/qr/design','PATCH',$data);
$request->setUserResolver(fn()=>$user);
$controller->saveDesign($request);
if ($restaurant->settings['unrelated'] !== 'preserved' || $restaurant->settings['qr_sticker']['logo_size'] !== 24 || $restaurant->settings['qr_sticker']['legacy'] !== 'preserved') {
    throw new RuntimeException('Settings were not preserved correctly');
}
foreach (['logo_size'=>200,'qr_size'=>5,'accent_color'=>'invalid','room_scan_text'=>str_repeat('x',41)] as $key=>$value) {
    $request = Illuminate\Http\Request::create('/restaurant/tables/qr/design','PATCH',array_merge($data,[$key=>$value]));
    $request->setUserResolver(fn()=>$user);
    try {
        $controller->saveDesign($request);
        throw new RuntimeException("Invalid $key accepted");
    } catch (Illuminate\Validation\ValidationException $e) {
        if (!isset($e->errors()[$key])) throw $e;
    }
}
echo "QR save, settings preservation and invalid input checks passed.\n";
