<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\Restaurant;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

Route::middleware('locale')->group(function () {
    Route::get('/', [PublicController::class, 'landing'])->name('home');
    Route::view('/demo', 'public.demo')->name('demo');
    Route::post('/demo-request', [PublicController::class, 'storeDemoRequest'])->middleware('throttle:5,1')->name('demo-requests.store');
    Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');
});
Route::get('/sitemap.xml', [PublicController::class, 'sitemap'])->name('sitemap');

// Readiness complements /up by checking whether the database can answer a query.
Route::get('/ready', function () {
    try {
        \Illuminate\Support\Facades\DB::select('SELECT 1');
        return response()->json(['status' => 'ok'])->header('Cache-Control', 'no-store');
    } catch (\Throwable $exception) {
        report($exception);
        return response()->json(['status' => 'unavailable'], 503)->header('Cache-Control', 'no-store');
    }
})->middleware('throttle:60,1')->name('ready');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/setup', [SetupController::class, 'show'])->name('setup.show');
    Route::post('/setup/run', [SetupController::class, 'run'])->name('setup.run');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/r/{restaurant_slug}/table/{table_number}', [MenuController::class, 'show'])
    ->middleware(['locale', 'throttle:300,1'])
    ->name('menu.show');
Route::post('/r/{restaurant_slug}/table/{table_number}/orders', [OrderController::class, 'store'])->middleware(['locale', 'throttle:30,1'])->name('orders.store');
Route::patch('/r/{restaurant_slug}/table/{table_number}/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
Route::post('/r/{restaurant_slug}/table/{table_number}/service-requests', [ServiceRequestController::class, 'store'])->middleware(['locale', 'throttle:30,1'])->name('service-requests.store');
Route::get('/r/{restaurant_slug}/table/{table_number}/confirmation', [MenuController::class, 'confirmation'])->middleware('locale')->name('menu.confirmation');

Route::middleware(['auth', 'role:restaurant_owner,staff', 'locale'])->prefix('restaurant')->name('restaurant.')->group(function () {
    Route::get('/access-required', [Restaurant\AccessController::class, 'show'])->name('access-required');

    // Profile selection (after login, before dashboard)
    Route::get('/profile-select', [AuthController::class, 'showProfileSelect'])->name('profile-select');
    Route::post('/profile-login', [AuthController::class, 'profileLogin'])->middleware('throttle:10,1')->name('profile-login');

    Route::middleware(['restaurant.access', 'profile.selected'])->group(function () {
        Route::get('/dashboard', [Restaurant\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics', [Restaurant\DashboardController::class, 'analytics'])->name('analytics');
        Route::get('/orders', [Restaurant\DashboardController::class, 'orders'])->name('orders.index');
        Route::post('/orders/manual', [Restaurant\DashboardController::class, 'storeManualOrder'])->name('orders.manual.store');
        Route::get('/orders/poll', [Restaurant\DashboardController::class, 'poll'])->name('orders.poll');
        Route::patch('/orders/{order}/confirm', [Restaurant\DashboardController::class, 'confirmOrder'])->name('orders.confirm');
        Route::patch('/orders/{order}/credit-paid', [Restaurant\DashboardController::class, 'markCreditPaid'])->name('orders.credit-paid');
        Route::patch('/orders/{order}', [Restaurant\DashboardController::class, 'updateOrder'])->name('orders.update');
        Route::patch('/menu-items/reorder', [Restaurant\MenuItemController::class, 'reorder'])->name('menu-items.reorder');
        Route::patch('/menu-items/{menu_item}/availability', [Restaurant\MenuItemController::class, 'toggleAvailability'])->name('menu-items.availability');
        Route::patch('/menu-items/{menu_item}/remove-photo', [Restaurant\MenuItemController::class, 'removePhoto'])->name('menu-items.remove-photo');
        Route::resource('/menu-items', Restaurant\MenuItemController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('/categories', Restaurant\CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('/tables/{table}/qr', [Restaurant\TableController::class, 'qr'])->name('tables.qr');
        Route::get('/tables/qr/setup-pack', [Restaurant\TableController::class, 'setupPack'])->name('tables.setup-pack');
        // Accept a native POST so multipart logo uploads are parsed reliably;
        // PATCH remains supported for older clients/bookmarked forms.
        Route::match(['post', 'patch'], '/tables/qr/design', [Restaurant\TableController::class, 'saveDesign'])->name('tables.design');
        Route::get('/tables/qr/design', fn () => redirect()->route('restaurant.tables.index'))->name('tables.design.open');
        Route::resource('/tables', Restaurant\TableController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('/service-requests', [Restaurant\ServiceRequestController::class, 'index'])->name('service-requests.index');
        Route::patch('/service-requests/{serviceRequest}', [Restaurant\ServiceRequestController::class, 'update'])->name('service-requests.update');
        Route::get('/settings', [Restaurant\SettingsController::class, 'edit'])->name('settings.edit');
        Route::patch('/settings', [Restaurant\SettingsController::class, 'update'])->name('settings.update');

        // Cashier reports (owner/manager only)
        Route::get('/cashier-reports', [Restaurant\CashierReportController::class, 'index'])->name('cashier-reports');

        // Delivery orders (owner/manager only)
        Route::get('/delivery', [Restaurant\DeliveryOrderController::class, 'index'])->name('delivery.index');
        Route::post('/delivery', [Restaurant\DeliveryOrderController::class, 'store'])->name('delivery.store');

        // Staff profile management (owner/manager only)
        Route::get('/staff-profiles', [Restaurant\StaffProfileController::class, 'index'])->name('staff-profiles.index');
        Route::get('/staff-profiles/{staffProfile}', [Restaurant\StaffProfileController::class, 'show'])->name('staff-profiles.show');
        Route::post('/staff-profiles', [Restaurant\StaffProfileController::class, 'store'])->name('staff-profiles.store');
        Route::patch('/staff-profiles/{staffProfile}', [Restaurant\StaffProfileController::class, 'update'])->name('staff-profiles.update');
        Route::delete('/staff-profiles/{staffProfile}', [Restaurant\StaffProfileController::class, 'destroy'])->name('staff-profiles.destroy');
    });
});

Route::middleware(['auth', 'role:admin', 'locale'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/database', [Admin\DashboardController::class, 'database'])->name('database');
    Route::post('/setup-run', [SetupController::class, 'run'])->name('setup.run');
    Route::post('/database/menu-refresh', [SetupController::class, 'refreshTulipMenu'])->name('database.menu-refresh');
    Route::post('/menu-import', [Admin\MenuImportController::class, 'store'])->name('menu-import.store');
    Route::patch('/restaurants/{restaurant}/password', [Admin\RestaurantController::class, 'updatePassword'])->name('restaurants.password.update');
    Route::post('/restaurants/{restaurant}/staff-profiles', [Admin\StaffProfileController::class, 'store'])->name('restaurants.staff-profiles.store');
    Route::patch('/restaurants/{restaurant}/staff-profiles/{staffProfile}', [Admin\StaffProfileController::class, 'update'])->name('restaurants.staff-profiles.update');
    Route::delete('/restaurants/{restaurant}/staff-profiles/{staffProfile}', [Admin\StaffProfileController::class, 'destroy'])->name('restaurants.staff-profiles.destroy');
    Route::resource('/restaurants', Admin\RestaurantController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('/users', Admin\UserController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/demo-requests', [Admin\DemoRequestController::class, 'index'])->name('demo-requests.index');
    Route::patch('/demo-requests/{demoRequest}', [Admin\DemoRequestController::class, 'update'])->name('demo-requests.update');
    Route::resource('/subscriptions', Admin\SubscriptionController::class)->only(['index', 'store', 'update']);
    Route::get('/payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/{subscription}/mark-paid', [Admin\PaymentController::class, 'markPaid'])->name('payments.mark-paid');
    Route::get('/payment-settings', [Admin\PaymentController::class, 'settings'])->name('payment-settings.index');
    Route::post('/payment-settings', [Admin\PaymentController::class, 'saveSettings'])->name('payment-settings.save');
});
