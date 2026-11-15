<?php
$app=require __DIR__.'/e2e-bootstrap.php';
use Illuminate\Support\Facades\DB;
if(DB::connection()->getDatabaseName()!==realpath(__DIR__.'/..').'/tmp/e2e/test.sqlite')throw new RuntimeException('Not isolated');
Illuminate\Support\Facades\Schema::dropAllTables();
$files=['0001_01_01_000000_create_users_table','2026_06_01_000000_create_zemtab_tables','2026_06_03_000000_add_dashboard_access_to_restaurants','2026_06_05_000000_add_business_type_to_restaurants','2026_06_06_000000_add_guest_sessions_and_payment_proofs','2026_06_21_000000_add_payment_method_to_subscriptions','2026_06_23_000000_add_scale_readiness_indexes_and_menu_cache_version','2026_06_24_210000_create_staff_profiles_and_order_enhancements','2026_06_25_000000_add_confirmed_at_to_orders','2026_07_16_000000_add_kitchen_screen_setting','2026_07_17_000000_add_menu_image_source','2026_09_17_010000_add_location_type_to_restaurant_tables','2026_09_20_000000_add_client_request_id_to_orders'];
foreach($files as $file)(require __DIR__.'/../database/migrations/'.$file.'.php')->up();
$ids=[];
foreach(['nazra','other','full'] as $name){
 $r=App\Models\Restaurant::create(['name'=>'E2E '.ucfirst($name),'slug'=>'e2e-'.$name,'business_type'=>'hotel','is_active'=>true,'dashboard_access_status'=>'active','settings'=>['service_mode'=>$name==='full'?'full':'menu_only','menu_photos_enabled'=>false,'information_board_enabled'=>true,'information_board'=>['title'=>'Hotel Guide','sections'=>[['title'=>'Breakfast','body'=>'Breakfast is served from 6:30.']]]]]);
 $u=App\Models\User::create(['name'=>'Owner '.$name,'email'=>$name.'@e2e.example.com','password'=>'ZemTab-E2E-Only-2026!','role'=>'restaurant_owner','restaurant_id'=>$r->id]);
 $sub=App\Models\Subscription::create(['restaurant_id'=>$r->id,'plan_name'=>'Digital Menu','monthly_price'=>13800,'status'=>'active','starts_at'=>'2026-11-01','ends_at'=>$name==='nazra'?'2026-11-30':'2027-01-31']);
 $cat=$r->categories()->create(['name'=>'Food','sort_order'=>1,'is_active'=>true]);
 $item=$r->menuItems()->create(['category_id'=>$cat->id,'name'=>'Soup','description'=>'Fresh soup','price'=>150,'is_available'=>true]);
 foreach(['table','room'] as $type)$r->tables()->create(['table_number'=>$type==='room'?'204':'1','location_type'=>$type,'is_active'=>true]);
 $ids[$name]=['restaurant'=>$r->id,'user'=>$u->id,'subscription'=>$sub->id,'item'=>$item->id,'category'=>$cat->id];
}
App\Models\User::create(['name'=>'E2E Admin','email'=>'admin@e2e.example.com','password'=>'ZemTab-E2E-Only-2026!','role'=>'admin']);
file_put_contents(__DIR__.'/../tmp/e2e/ids.json',json_encode($ids));echo "Isolated database seeded.\n";
