<?php
$app=require __DIR__.'/e2e-bootstrap.php';
$ids=json_decode(file_get_contents(__DIR__.'/../tmp/e2e/ids.json'),true);
$order=App\Models\Order::where('restaurant_id',$ids['full']['restaurant'])->latest('id')->first();
echo json_encode(['order'=>$order?->toArray(),'order_count'=>App\Models\Order::where('restaurant_id',$ids['full']['restaurant'])->count(),'requests'=>App\Models\ServiceRequest::where('restaurant_id',$ids['full']['restaurant'])->count()]);
