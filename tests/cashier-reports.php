<?php

// In-memory fixture and controller regression suite; never accesses production.
require __DIR__.'/release-workflow.php';

use App\Models\{Order, Restaurant, StaffProfile, User};
use Illuminate\Http\Request;
use Illuminate\Session\{ArraySessionHandler, Store};
use Illuminate\Support\Facades\DB;

$schema->create('staff_profiles', function ($table) {
    $table->id(); $table->unsignedBigInteger('restaurant_id');
    $table->string('name'); $table->string('role'); $table->timestamps();
});
$schema->table('orders', fn ($table) => $table->unsignedBigInteger('handled_by_profile_id')->nullable());
$venue = $sentinel;
$venue->update(['settings' => ['payment_methods' => ['cash']]]);
$manager = StaffProfile::create(['restaurant_id' => $venue->id, 'name' => 'Manager', 'role' => 'owner_manager']);
$cashier = StaffProfile::create(['restaurant_id' => $venue->id, 'name' => 'Cashier', 'role' => 'cashier']);
$foreign = Restaurant::create(['name' => 'Other venue', 'slug' => 'other']);
$make = fn ($extra) => Order::create(array_merge(['restaurant_id' => $venue->id, 'table_number' => '101', 'status' => 'completed', 'payment_status' => 'paid', 'payment_method' => 'cash', 'total' => 100], $extra));
// Rollback also prevents WorkBoardRevision after-commit writes from this fixture.
DB::beginTransaction();
try {
    $make(['handled_by_profile_id' => $manager->id, 'payment_method' => 'telebirr', 'total' => 200]);
    $make(['handled_by_profile_id' => $cashier->id]);
    $make(['handled_by_profile_id' => null, 'payment_method' => null, 'total' => 50]);
    $make(['handled_by_profile_id' => $manager->id, 'payment_method' => 'credit', 'payment_status' => 'unpaid', 'total' => 75]);
    $make(['status' => 'new', 'total' => 999]);
    $make(['status' => 'cancelled', 'total' => 999]);
    $make(['restaurant_id' => $foreign->id, 'total' => 999]);
    $make(['created_at' => now()->subYears(2), 'total' => 999]);
    // created_at is not fillable; explicitly move this final fixture outside the range.
    Order::latest('id')->first()->forceFill(['created_at' => now()->subYears(2)])->save();
    $user = new User(['restaurant_id' => $venue->id]);
    $user->setRelation('restaurant', $venue);
    $request = Request::create('/reports');
    $request->setUserResolver(fn () => $user);
    $session = new Store('report-test', new ArraySessionHandler(10));
    $session->start(); $session->put('staff_profile_role', 'owner_manager');
    $request->setLaravelSession($session);
    $queries = 0;
    DB::listen(function () use (&$queries) { $queries++; });
    $report = app(App\Http\Controllers\Restaurant\CashierReportController::class)->index($request)->getData();
    if ($report['grand_total'] !== 425.0 || $report['grand_orders'] !== 4
        || $report['payment_method_totals']['telebirr'] !== 200.0
        || $report['payment_method_totals']['unspecified'] !== 50.0
        || $report['payment_method_totals']['credit'] !== 75.0
        || $report['cashiers']->firstWhere('cashier.id', $manager->id)['order_count'] !== 2
        || $queries !== 2) {
        throw new RuntimeException('Report totals, manager attribution, historical methods, filtering or query budget failed.');
    }
    echo "Cashier report passed: manager, cashier, unassigned, historical methods, credit, date/tenant/status exclusions; two queries.\n";
} finally {
    DB::rollBack();
}
