<?php

namespace App\Support;

use App\Http\Controllers\Restaurant\DashboardController;
use App\Models\{Order, Restaurant, RestaurantTable, User};
use Illuminate\Http\Request;
use Illuminate\Session\{ArraySessionHandler, Store};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/** Bounded controller integration tests. Never commits synthetic business data. */
class ReleaseWorkflowTest
{
    public function run(): array
    {
        $checks = [];
        $ids = [];
        $connection = DB::connection();
        $level = $connection->transactionLevel();
        $started = microtime(true);
        try {
            if ($level !== 0) {
                throw new RuntimeException('An existing transaction prevents isolated testing.');
            }
            if ($connection->getDriverName() === 'mysql') {
                $tables = $connection->select("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('restaurants', 'orders', 'restaurant_tables')");
                if (count($tables) !== 3 || collect($tables)->contains(fn ($table) => strtoupper((string) $table->ENGINE) !== 'INNODB')) {
                    throw new RuntimeException('Transactional storage is required.');
                }
            }
            $connection->beginTransaction();
            $venue = Restaurant::create(['name' => 'Private release test', 'slug' => 'release-test-'.Str::uuid(), 'business_type' => 'hotel', 'is_active' => false, 'kitchen_screen_enabled' => true]);
            $ids[] = $venue->id;
            $other = Restaurant::create(['name' => 'Private isolation test', 'slug' => 'release-test-'.Str::uuid(), 'business_type' => 'restaurant', 'is_active' => false]);
            $ids[] = $other->id;
            $user = new User(['role' => 'restaurant_owner', 'restaurant_id' => $venue->id]);
            $user->setRelation('restaurant', $venue);
            $controller = app(DashboardController::class);
            $visits = app(GuestVisitManager::class);
            $request = function (array $data = [], string $role = 'owner_manager') use ($user): Request {
                $request = Request::create('/release-test/internal', 'POST', $data, [], [], ['HTTP_ACCEPT' => 'application/json']);
                $session = new Store('release-test', new ArraySessionHandler(10));
                $session->start();
                $session->put('staff_profile_role', $role);
                $request->setLaravelSession($session);
                $request->setUserResolver(fn () => $user);
                return $request;
            };
            $order = fn (array $extra = []) => Order::create(array_merge(['restaurant_id' => $venue->id, 'table_number' => 'test-101', 'status' => 'new', 'payment_status' => 'unpaid', 'subtotal' => 100, 'total' => 100, 'order_type' => 'dine_in', 'confirmed_at' => now()], $extra));
            $assert = function (bool $condition): void {
                if (! $condition) throw new RuntimeException('Expected state was not observed.');
            };
            $test = function (string $name, callable $action, ?int $expectedError = null) use (&$checks, $started): void {
                if (microtime(true) - $started > 8) {
                    $checks[] = ['group' => 'Workflow integration', 'name' => $name, 'status' => 'NOT RUN', 'detail' => 'Eight-second workflow safety budget reached.'];
                    return;
                }
                $begin = hrtime(true);
                $status = 'PASS';
                $detail = 'Controller assertions passed; HTTP middleware and browser UI are not exercised.';
                try {
                    $action();
                    if ($expectedError !== null) throw new RuntimeException('Expected rejection was missing.');
                } catch (Throwable $e) {
                    if ($expectedError !== null && $e instanceof HttpExceptionInterface && $e->getStatusCode() === $expectedError) {
                        $detail = 'Controller rejected the operation with HTTP '.$expectedError.'.';
                    } else {
                        $status = 'FAIL';
                        $detail = 'Assertion or execution failed: '.class_basename($e).'; code '.$e->getCode().'.';
                    }
                }
                $checks[] = ['group' => 'Workflow integration', 'name' => $name, 'status' => $status, 'detail' => $detail, 'duration_ms' => round((hrtime(true) - $begin) / 1e6, 2)];
            };
            foreach (Order::PAYMENT_METHODS as $method) {
                $test('Complete order using '.$method, function () use ($order, $controller, $request, $visits, $method, $assert) {
                    $row = $order();
                    $controller->updateOrder($request(['status' => 'completed', 'payment_method' => $method]), $row, $visits);
                    $row->refresh();
                    $credit = in_array($method, ['credit', 'room_credit'], true);
                    $assert($row->status === 'completed' && $row->payment_method === $method && $row->payment_status === ($credit ? 'unpaid' : 'paid'));
                    if ($credit) {
                        $controller->markCreditPaid($request(), $row, $visits);
                        $assert($row->fresh()->payment_status === 'paid');
                    }
                });
            }
            $test('Confirm, prepare and serve order', function () use ($order, $controller, $request, $visits, $assert) {
                $row = $order(['confirmed_at' => null]);
                $controller->confirmOrder($request(), $row);
                foreach (['preparing', 'served'] as $status) $controller->updateOrder($request(['status' => $status], 'kitchen'), $row, $visits);
                $assert($row->fresh()->status === 'served');
            });
            $test('Cashier cancels unpaid order', function () use ($order, $controller, $request, $visits, $assert) {
                $row = $order();
                $controller->updateOrder($request(['status' => 'cancelled'], 'cashier'), $row, $visits);
                $assert($row->fresh()->status === 'cancelled');
            });
            $test('Reject cancellation of paid order', fn () => $controller->updateOrder($request(['status' => 'cancelled']), $order(['status' => 'paid', 'payment_status' => 'paid']), $visits), 422);
            $test('Reject completion without payment method', fn () => $controller->updateOrder($request(['status' => 'completed']), $order(), $visits), 422);
            $test('Reject unconfirmed order preparation', fn () => $controller->updateOrder($request(['status' => 'preparing'], 'kitchen'), $order(['confirmed_at' => null]), $visits), 403);
            $test('Reject kitchen cancellation', fn () => $controller->updateOrder($request(['status' => 'cancelled'], 'kitchen'), $order(), $visits), 403);
            $test('Reject cross-venue order update', fn () => $controller->updateOrder($request(['status' => 'cancelled']), $order(['restaurant_id' => $other->id]), $visits), 403);
            $test('Reject reopening completed order', fn () => $controller->updateOrder($request(['status' => 'new']), $order(['status' => 'completed']), $visits), 422);
            $test('Room and table QR labels and instructions', function () use ($venue, $assert) {
                foreach (['table' => 'Scan to order', 'room' => 'Scan for room service'] as $type => $instruction) {
                    $point = RestaurantTable::create(['restaurant_id' => $venue->id, 'table_number' => $type.'-101', 'location_type' => $type, 'is_active' => false]);
                    $point->setRelation('restaurant', $venue);
                    $assert($point->scanInstruction() === $instruction && str_contains($point->displayLabel(), '101'));
                }
            });
        } catch (Throwable $e) {
            $checks[] = ['group' => 'Workflow integration', 'name' => 'Isolated fixture setup', 'status' => 'FAIL', 'detail' => 'Unable to safely complete suite: '.class_basename($e).'; code '.$e->getCode().'.'];
        } finally {
            try {
                if ($connection->transactionLevel() > $level) $connection->rollBack($level);
                $clean = $ids === [] || (! Restaurant::whereIn('id', $ids)->exists() && ! Order::whereIn('restaurant_id', $ids)->exists() && ! RestaurantTable::whereIn('restaurant_id', $ids)->exists());
                $checks[] = ['group' => 'Cleanup', 'name' => 'Synthetic records rolled back', 'status' => $clean ? 'PASS' : 'FAIL', 'detail' => $clean ? 'No test venue, order or table rows remain. No uploads, real users, messages or payments were created. Auto-increment gaps can remain.' : 'Cleanup verification failed; administrator investigation required.'];
            } catch (Throwable $e) {
                $checks[] = ['group' => 'Cleanup', 'name' => 'Rollback verification', 'status' => 'FAIL', 'detail' => 'Cleanup could not be verified: '.class_basename($e).'.'];
            }
        }
        return $checks;
    }
}
