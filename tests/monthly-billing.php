<?php

// Real models/controllers and migration, with SQLite in memory only.
$app = require __DIR__.'/e2e-bootstrap.php';
config(['database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
if (Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== ':memory:') throw new RuntimeException('Not isolated');
foreach (['0001_01_01_000000_create_users_table', '2026_06_01_000000_create_zemtab_tables', '2026_06_03_000000_add_dashboard_access_to_restaurants', '2026_06_21_000000_add_payment_method_to_subscriptions'] as $file) {
    (require __DIR__.'/../database/migrations/'.$file.'.php')->up();
}

use App\Models\Restaurant;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

$checks = 0;
function verify($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
foreach (['2026-01-31'=>'2026-02-01', '2026-02-28'=>'2026-03-01', '2028-02-29'=>'2028-03-01', '2026-12-31'=>'2027-01-01', '2026-11-01'=>'2026-11-01'] as $input=>$output) {
    verify(Subscription::monthlyDueDate($input)->toDateString() === $output, 'Calendar alignment '.$input);
}
foreach (['active', 'trial', 'unpaid'] as $status) {
    $sub = new Subscription(['status'=>$status, 'ends_at'=>'2028-02-01']);
    foreach (['2028-01-31 23:59:59'=>[true,false,null], '2028-02-01 00:00:00'=>[true,true,7], '2028-02-04 12:00:00'=>[true,true,4], '2028-02-07 23:59:59'=>[true,true,1], '2028-02-08 00:00:00'=>[false,false,null]] as $time=>$expected) {
        Carbon::setTestNow($time);
        verify($sub->allowsServiceAccess() === $expected[0], $status.' access '.$time);
        verify($sub->isInPaymentGrace() === $expected[1], $status.' grace '.$time);
        verify($sub->graceDaysRemaining() === $expected[2], $status.' days left '.$time);
        if ($expected[1]) verify($sub->effectiveStatus() === 'payment_due', 'Due badge during grace');
    }
    verify($sub->isExpired(), 'Expired after the inclusive grace period');
}

Carbon::setTestNow('2026-11-15 12:00:00');
$venue = Restaurant::create(['name'=>'Billing fixture','slug'=>'billing-fixture','is_active'=>true,'dashboard_access_status'=>'active']);
$sub = Subscription::create(['restaurant_id'=>$venue->id,'plan_name'=>'Pro','monthly_price'=>13800,'status'=>'active','starts_at'=>'2026-11-15','ends_at'=>'2026-11-30']);
verify($sub->fresh()->ends_at->toDateString() === '2026-12-01', 'Model writes the first');
$undated = Subscription::create(['restaurant_id'=>$venue->id,'plan_name'=>'Trial','status'=>'trial']);
verify($undated->ends_at->toDateString() === '2026-12-01', 'New undated subscription defaults to next first');

$session = new Illuminate\Session\Store('monthly-billing',new Illuminate\Session\ArraySessionHandler(120));
$session->start();
function requestFor(array $data): Request {
    global $session; $request=Request::create('/admin/payments','POST',$data); $request->setLaravelSession($session); app()->instance('request',$request); return $request;
}
$controller = new App\Http\Controllers\Admin\PaymentController;
foreach ([
    ['2026-12-01 12:00:00','2026-12-01',[], '2027-01-01'],
    ['2026-12-07 23:59:59','2026-12-01',[], '2027-01-01'],
    ['2026-12-08 00:00:00','2026-12-01',[], '2027-01-01'],
    ['2027-02-20 12:00:00','2026-12-01',[], '2027-03-01'],
    ['2026-11-20 12:00:00','2026-12-01',[], '2027-01-01'],
    ['2026-12-07 12:00:00','2026-12-01',['renew_months'=>3], '2027-03-01'],
    ['2026-12-07 12:00:00','2026-12-01',['extend_days'=>30], '2027-01-01'],
    ['2026-12-07 12:00:00','2026-12-01',['custom_ends_at'=>'2026-12-31'], '2027-01-01'],
    ['2028-02-07 12:00:00','2028-02-01',[], '2028-03-01'],
] as [$time,$due,$options,$expected]) {
    Carbon::setTestNow($time);
    $sub->refresh()->update(['ends_at'=>$due,'status'=>'unpaid']);
    $venue->update(['dashboard_access_status'=>'payment_required']);
    $response=$controller->markPaid(requestFor($options+['payment_method'=>'cash']),$sub->fresh());
    $renewed=$sub->fresh();
    verify($response->getStatusCode()===302,'Payment returns successfully');
    verify($renewed->ends_at->toDateString()===$expected,'Renewal stays on calendar first '.$time);
    verify($venue->fresh()->serviceAccessAllowed($renewed),'Verified payment restores access immediately');
    verify((float)$renewed->monthly_price===13800.0,'No fee or penalty changes');
}
foreach ([-7, 1, 7] as $days) {
    try {
        $controller->markPaid(requestFor(['extend_days'=>$days]),$sub->fresh());
        verify(false,'Arbitrary legacy day adjustments must not drift billing');
    } catch (Illuminate\Validation\ValidationException $exception) {
        verify(isset($exception->errors()['extend_days']),'Arbitrary day adjustments rejected');
    }
}

Carbon::setTestNow('2026-11-15 12:00:00');
foreach ([['2026-10-17','2026-11-01'],['2026-11-01','2026-11-01'],[null,'2026-12-01']] as [$old,$new]) {
    $id=DB::table('subscriptions')->insertGetId(['restaurant_id'=>$venue->id,'plan_name'=>'Legacy','monthly_price'=>2500,'status'=>'active','starts_at'=>'2026-01-01','ends_at'=>$old]);
    $migration=require __DIR__.'/../database/migrations/2026_11_15_000000_align_subscription_billing_to_month_start.php';
    $migration->up();
    $row=DB::table('subscriptions')->find($id);
    verify(substr($row->ends_at,0,10)===$new,'Existing date migration '.$old);
    verify((float)$row->monthly_price===2500.0 && $row->status==='active','Migration preserves price and status');
}
verify($venue->fresh()->is_active,'Migration preserves account access overrides');
// Render the real shared warning and exercise both access middleware boundaries.
$warningVenue=Restaurant::create(['name'=>'Calendar warning','slug'=>'calendar-warning','is_active'=>true,'dashboard_access_status'=>'active','settings'=>['service_mode'=>'menu_only']]);
$warningSub=$warningVenue->subscriptions()->create(['plan_name'=>'Digital Menu','monthly_price'=>13800,'status'=>'active','ends_at'=>'2026-12-01']);
$user=new App\Models\User(['name'=>'Fixture owner','role'=>'restaurant_owner']);
$user->setRelation('restaurant',$warningVenue);
Illuminate\Support\Facades\Auth::setUser($user);
$session->put('staff_profile_role','owner_manager');
view()->share('errors',new Illuminate\Support\ViewErrorBag);
foreach ([1=>7,7=>1,8=>0] as $day=>$remaining) {
    Carbon::setTestNow(sprintf('2026-12-%02d 12:00:00',$day));
    $request=Request::create('/restaurant/menu-items');$request->setLaravelSession($session);$request->setUserResolver(fn()=>$user);
    $request->setRouteResolver(fn()=>app('router')->getRoutes()->getByName('restaurant.menu-items.index'));
    app()->instance('request',$request);
    $response=(new App\Http\Middleware\EnsureRestaurantDashboardAccess)->handle($request,fn()=>response('allowed'));
    verify($response->getStatusCode()===($day===8?302:200),'Dashboard boundary day '.$day);
    if ($remaining) {
        $html=view('layouts.dashboard',['heading'=>'Billing fixture'])->render();
        verify(str_contains($html,'payment-grace-warning'),'Prominent warning on day '.$day);
        verify(str_contains($html,'role="alert"'),'Warning announced on day '.$day);
        verify(str_contains($html,$remaining===1?'1 day remains':'7 days remain'),'Inclusive days shown on day '.$day);
        verify(str_contains($html,'Dec 8, 2026'),'Lock date shown on day '.$day);
    }
    $guest=Request::create('/r/calendar-warning/menu');
    $route=clone app('router')->getRoutes()->getByName('menu.catalog');$route->bind($guest);$route->setParameter('restaurant_slug',$warningVenue->slug);$guest->setRouteResolver(fn()=>$route);
    $response=(new App\Http\Middleware\EnsurePublicSubscriptionAccess)->handle($guest,fn()=>response('guest allowed'));
    verify($response->getStatusCode()===($day===8?403:200),'Guest boundary day '.$day);
}
$adminUser=new App\Models\User(['name'=>'Fixture admin','role'=>'admin']);
Illuminate\Support\Facades\Auth::setUser($adminUser);
$adminRequest=requestFor([]);$adminRequest->setRouteResolver(fn()=>app('router')->getRoutes()->getByName('admin.payments.index'));
$payments=$controller->index()->render();
verify(str_contains($payments,'name="renew_months"'),'Payment controls renew by calendar month');
verify(!str_contains($payments,'Custom days (+/-)'),'Arbitrary-day controls removed');
verify(str_contains($payments,'locks on the 8th'),'Admin payment policy visible');
$subscriptionsController=new App\Http\Controllers\Admin\SubscriptionController;
$subscriptions=$subscriptionsController->index()->render();
verify(str_contains($subscriptions,'All subscriptions renew on the 1st'),'Subscription policy visible');
$subscriptionsController->store(requestFor(['restaurant_id'=>$warningVenue->id,'plan_name'=>'Calendar creation','status'=>'active','monthly_price'=>12000,'ends_at'=>'2027-01-18']));
$created=Subscription::where('plan_name','Calendar creation')->firstOrFail();
verify($created->ends_at->toDateString()==='2027-02-01','Real admin create aligns dates');
$subscriptionsController->update(requestFor(['restaurant_id'=>$warningVenue->id,'plan_name'=>'Calendar creation','status'=>'active','monthly_price'=>12000,'ends_at'=>'2027-02-14']),$created);
verify($created->fresh()->ends_at->toDateString()==='2027-03-01','Real admin edit aligns dates');
Carbon::setTestNow();
echo "Passed {$checks} calendar billing checks, including migration, leap years, grace boundaries and real payment renewal.\n";
