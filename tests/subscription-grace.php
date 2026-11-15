<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$checks=0;
function check($ok,$message){global $checks;if(!$ok)throw new RuntimeException($message);$checks++;}
foreach(['active','trial','unpaid'] as $status){
 $sub=new App\Models\Subscription(['status'=>$status,'ends_at'=>'2026-11-30']);
 foreach(['2026-11-30 23:59:59'=>true,'2026-12-01 00:00:00'=>true,'2026-12-07 23:59:59'=>true,'2026-12-08 00:00:00'=>false,'2027-02-01 12:00:00'=>false] as $time=>$expected){
  Illuminate\Support\Carbon::setTestNow($time);
  check($sub->allowsServiceAccess()===$expected,$status.' '.$time);
  check($sub->ends_at->toDateString()==='2026-11-30','Grace does not modify paid-through date');
 }
}
Illuminate\Support\Carbon::setTestNow('2026-12-07 23:59:59');
$sub=new App\Models\Subscription(['status'=>'active','ends_at'=>'2026-11-30']);
check($sub->isInPaymentGrace(),'Last second of day 7 is grace');
$venue=new App\Models\Restaurant(['is_active'=>true,'dashboard_access_status'=>'active']);
check($venue->serviceAccessAllowed($sub),'Venue allowed within grace');
$venue->dashboard_access_status='revoked';check(!$venue->serviceAccessAllowed($sub),'Manual revocation is preserved');
$venue->dashboard_access_status='payment_required';check(!$venue->serviceAccessAllowed($sub),'Explicit active-subscription payment hold preserved');
$sub->status='unpaid';check($venue->serviceAccessAllowed($sub),'Automatically flagged unpaid subscription receives grace');
$venue->is_active=false;check(!$venue->serviceAccessAllowed($sub),'Inactive venue never bypasses hold');
$sub->status='cancelled';check(!$sub->allowsServiceAccess(),'Cancelled subscription has no grace');
$sub->ends_at=null;check(!$sub->allowsServiceAccess(),'Cancelled undated subscription blocked');
$sub->status='unpaid';check(!$sub->allowsServiceAccess(),'Unpaid without a paid-through date blocked');
$sub->status='active';check($sub->allowsServiceAccess(),'Active undated plan preserved');
Illuminate\Support\Carbon::setTestNow('2026-12-08 00:00:00');$sub->ends_at='2026-12-31';check($sub->allowsServiceAccess(),'Verified renewal restores access immediately');
// A still-open workboard must stop polling when the grace deadline passes.
config(['cache.stores.file' => ['driver' => 'array']]);
Illuminate\Support\Facades\Cache::purge('file');
Illuminate\Support\Facades\Cache::store('file')->put(App\Support\PublicMenuCache::modeKey(999999), false, 3600);
$session = new Illuminate\Session\Store('billing-test', new Illuminate\Session\ArraySessionHandler(120));
$session->start();
$session->put('billing_access_until', now()->subSecond()->getTimestamp());
$request = Illuminate\Http\Request::create('/poll', 'GET', ['session' => hash('sha256', $session->getId())]);
$request->setLaravelSession($session);
try {
 (new App\Http\Controllers\Restaurant\DashboardController)->poll($request, 999999);
 check(false, 'Expired poll must be blocked before revision or database access');
} catch (Symfony\Component\HttpKernel\Exception\HttpException $exception) {
 check($exception->getStatusCode() === 403, 'Expired open workboard poll denied');
}
Illuminate\Support\Carbon::setTestNow();
echo "Passed {$checks} subscription grace and access boundary checks.\n";
