<?php
// Real guest controllers and models, isolated in-memory database only.
$app = require __DIR__.'/e2e-bootstrap.php';
config(['database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
if (Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== ':memory:') throw new RuntimeException('Not isolated');
foreach (['0001_01_01_000000_create_users_table', '2026_06_01_000000_create_zemtab_tables', '2026_06_03_000000_add_dashboard_access_to_restaurants', '2026_06_06_000000_add_guest_sessions_and_payment_proofs', '2026_06_24_210000_create_staff_profiles_and_order_enhancements', '2026_06_25_000000_add_confirmed_at_to_orders', '2026_09_20_000000_add_client_request_id_to_orders'] as $file) (require __DIR__.'/../database/migrations/'.$file.'.php')->up();
use App\Models\Restaurant;
use App\Models\GuestSession;
use App\Models\Order;
use App\Support\GuestVisitManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

$checks = 0;
function checkGuest($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
$session = new Illuminate\Session\Store('guest-tests', new Illuminate\Session\ArraySessionHandler(120)); $session->start();
app()->instance('session.store', $session);
view()->share('errors', new Illuminate\Support\ViewErrorBag());
function guestRequest($data = [], $cookies = [], $json = true) {
    global $session;
    $request = Request::create('/r/guest-test/table/1', 'POST', $data, $cookies, [], $json ? ['HTTP_ACCEPT'=>'application/json'] : []);
    $request->setLaravelSession($session); app()->instance('request', $request); return $request;
}
$r = Restaurant::create(['name'=>'Guest Test', 'slug'=>'guest-test', 'is_active'=>true, 'settings'=>['service_mode'=>'full','service_charge_percentage'=>10,'vat_percentage'=>15]]);
$table = $r->tables()->create(['table_number'=>'1','is_active'=>true]);
$otherTable = $r->tables()->create(['table_number'=>'2','is_active'=>true]);
$cat = $r->categories()->create(['name'=>'Food','is_active'=>true]);
$item = $r->menuItems()->create(['category_id'=>$cat->id,'name'=>'Tiramisu','price'=>750,'is_available'=>true]);
$visits = new GuestVisitManager;
$visit = $visits->resolve(guestRequest(), $r, $table);
$cookieName = $visits->cookieName($r, $table); $cookies = [$cookieName=>$visit->token];
$service = new App\Http\Controllers\ServiceRequestController;
for ($i=0; $i<3; $i++) {
    $response = $service->store(guestRequest(['type'=>'call_waiter'], $cookies), $visits, $r->slug, '1');
    checkGuest($response->getStatusCode() === 200 && $response->getData()->cooldown === 60, 'Request acknowledged with cooldown');
}
checkGuest($r->serviceRequests()->count() === 1, 'Repeated requests are deduplicated');
$service->store(guestRequest(['type'=>'request_bill'], $cookies), $visits, $r->slug, '1');
checkGuest($r->serviceRequests()->count() === 2, 'Different request types are independent');
$r->serviceRequests()->where('type','call_waiter')->update(['created_at'=>now()->subSeconds(61)]);
$service->store(guestRequest(['type'=>'call_waiter'], $cookies), $visits, $r->slug, '1');
checkGuest($r->serviceRequests()->count() === 3, 'Request allowed after cooldown');
$orderController = new App\Http\Controllers\OrderController;
$data = ['items'=>[['id'=>$item->id,'quantity'=>2,'note'=>'No cocoa']], 'note'=>'Please bring spoons', 'client_request_id'=>'guest-test-order'];
$response = $orderController->store(guestRequest($data,$cookies,false), $visits, $r->slug,'1');
checkGuest($r->orders()->exists(), 'Order created: '.json_encode($session->get('errors')?->all()).' redirect '.$response->getTargetUrl());
$order = $r->orders()->firstOrFail();
checkGuest(str_contains($response->getTargetUrl(), 'order='.$order->id), 'Receipt URL persists across reloads');
checkGuest((float)$order->total === 1875.0 && (float)$order->tax === 225.0 && (float)$order->service_charge === 150.0, 'Separate fees match server totals');
checkGuest($order->items()->first()->note === 'No cocoa', 'Item notes saved');
$orderController->store(guestRequest($data,$cookies,false),$visits,$r->slug,'1');
checkGuest($r->orders()->count() === 1, 'Order retry idempotent');
$menu = new App\Http\Controllers\MenuController;
// Avoid stale cached payloads from other isolated scripts.
Illuminate\Support\Facades\Cache::store('file')->forget(App\Support\PublicMenuCache::payloadKey($r->slug));
foreach (Order::STATUSES as $status) {
    $order->update(['status'=>$status]);
    $result = $menu->orderStatus(guestRequest([], $cookies),$r->slug,'1',(string)$order->id);
    checkGuest($result->getData()->status === $status, 'Live state '.$status);
    checkGuest(str_contains($result->headers->get('Cache-Control'), 'no-store'), 'Status never cached');
    checkGuest(array_keys((array)$result->getData()) === ['status'], 'No guest/private data exposed');
}
$visit->update(['closed_at'=>now()]);
checkGuest($menu->orderStatus(guestRequest([],$cookies),$r->slug,'1',(string)$order->id)->getStatusCode()===200,'Own closed receipt remains readable');
foreach ([[],[$cookieName=>'wrong-token'],[$visits->cookieName($r,$otherTable)=>$visit->token]] as $invalidCookies) {
    try { $menu->orderStatus(guestRequest([],$invalidCookies),$r->slug,'1',(string)$order->id); throw new RuntimeException('Leaked order'); }
    catch (HttpException $e) { checkGuest($e->getStatusCode()===404,'Foreign/missing visit denied'); }
}
$another = GuestSession::create(['restaurant_id'=>$r->id,'table_id'=>$table->id,'table_number'=>'1','token'=>'other-guest','expires_at'=>now()->addHour()]);
try { $menu->orderStatus(guestRequest([],[$cookieName=>$another->token]),$r->slug,'1',(string)$order->id); throw new RuntimeException('Leaked another visit'); }
catch (Illuminate\Database\Eloquent\ModelNotFoundException $e) { checkGuest(true,'Same table different visit denied'); }
$receiptRequest = guestRequest(['order'=>$order->id],$cookies,false); $receiptRequest->query->set('order',$order->id);
$receipt = $menu->confirmation($receiptRequest,$r->slug,'1')->getContent();
foreach (['Tiramisu','No cocoa','Please bring spoons','1,875.00','guestOrderProgress'] as $text) checkGuest(str_contains($receipt,$text),'Receipt contains '.$text);
$r->update(['settings'=>['service_mode'=>'menu_only']]);
Illuminate\Support\Facades\Cache::store('file')->forget(App\Support\PublicMenuCache::payloadKey($r->slug));
try { $menu->orderStatus(guestRequest([],$cookies),$r->slug,'1',(string)$order->id); throw new RuntimeException('Menu only status'); }
catch (HttpException $e) { checkGuest($e->getStatusCode()===404,'Menu-only status unavailable'); }
try { $service->store(guestRequest(['type'=>'call_waiter'],$cookies),$visits,$r->slug,'1'); throw new RuntimeException('Menu only request'); }
catch (HttpException $e) { checkGuest($e->getStatusCode()===403,'Menu-only service unavailable'); }
echo "Passed {$checks} guest experience checks.\n";
