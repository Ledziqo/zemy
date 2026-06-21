<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\Restaurant;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

Route::middleware('locale')->group(function () {
    Route::get('/', [PublicController::class, 'landing'])->name('home');
    Route::post('/demo-request', [PublicController::class, 'storeDemoRequest'])->middleware('throttle:5,1')->name('demo-requests.store');
    Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');
});
Route::get('/sitemap.xml', [PublicController::class, 'sitemap'])->name('sitemap');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/r/{restaurant_slug}/table/{table_number}', [MenuController::class, 'show'])
    ->middleware('throttle:300,1')
    ->name('menu.show');
Route::post('/r/{restaurant_slug}/table/{table_number}/orders', [OrderController::class, 'store'])->middleware('throttle:30,1')->name('orders.store');
Route::patch('/r/{restaurant_slug}/table/{table_number}/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
Route::post('/r/{restaurant_slug}/table/{table_number}/service-requests', [ServiceRequestController::class, 'store'])->middleware('throttle:30,1')->name('service-requests.store');
Route::post('/r/{restaurant_slug}/table/{table_number}/payment-proof', [PaymentProofController::class, 'store'])->name('payment-proofs.store');
Route::get('/r/{restaurant_slug}/table/{table_number}/confirmation', [MenuController::class, 'confirmation'])->name('menu.confirmation');

Route::middleware(['auth', 'role:restaurant_owner,staff', 'locale'])->prefix('restaurant')->name('restaurant.')->group(function () {
    Route::get('/access-required', [Restaurant\AccessController::class, 'show'])->name('access-required');
    Route::middleware('restaurant.access')->group(function () {
        Route::get('/dashboard', [Restaurant\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics', [Restaurant\DashboardController::class, 'analytics'])->name('analytics');
        Route::get('/orders', [Restaurant\DashboardController::class, 'orders'])->name('orders.index');
        Route::get('/orders/poll', [Restaurant\DashboardController::class, 'poll'])->name('orders.poll');
        Route::patch('/orders/{order}', [Restaurant\DashboardController::class, 'updateOrder'])->name('orders.update');
        Route::patch('/menu-items/reorder', [Restaurant\MenuItemController::class, 'reorder'])->name('menu-items.reorder');
        Route::patch('/menu-items/{menu_item}/availability', [Restaurant\MenuItemController::class, 'toggleAvailability'])->name('menu-items.availability');
        Route::patch('/menu-items/{menu_item}/remove-photo', [Restaurant\MenuItemController::class, 'removePhoto'])->name('menu-items.remove-photo');
        Route::resource('/menu-items', Restaurant\MenuItemController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('/categories', Restaurant\CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('/tables/{table}/qr', [Restaurant\TableController::class, 'qr'])->name('tables.qr');
        Route::get('/tables/qr/setup-pack', [Restaurant\TableController::class, 'setupPack'])->name('tables.setup-pack');
        Route::resource('/tables', Restaurant\TableController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('/service-requests', [Restaurant\ServiceRequestController::class, 'index'])->name('service-requests.index');
        Route::patch('/service-requests/{serviceRequest}', [Restaurant\ServiceRequestController::class, 'update'])->name('service-requests.update');
        Route::get('/settings', [Restaurant\SettingsController::class, 'edit'])->name('settings.edit');
        Route::patch('/settings', [Restaurant\SettingsController::class, 'update'])->name('settings.update');
    });
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/setup-run', [SetupController::class, 'run'])->name('setup.run');
    Route::patch('/restaurants/{restaurant}/password', [Admin\RestaurantController::class, 'updatePassword'])->name('restaurants.password.update');
    Route::resource('/restaurants', Admin\RestaurantController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('/users', Admin\UserController::class)->only(['index', 'store', 'update']);
    Route::get('/demo-requests', [Admin\DemoRequestController::class, 'index'])->name('demo-requests.index');
    Route::patch('/demo-requests/{demoRequest}', [Admin\DemoRequestController::class, 'update'])->name('demo-requests.update');
    Route::resource('/subscriptions', Admin\SubscriptionController::class)->only(['index', 'store', 'update']);
    Route::get('/payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/{subscription}/mark-paid', [Admin\PaymentController::class, 'markPaid'])->name('payments.mark-paid');
    Route::get('/payment-settings', [Admin\PaymentController::class, 'settings'])->name('payment-settings.index');
    Route::post('/payment-settings', [Admin\PaymentController::class, 'saveSettings'])->name('payment-settings.save');
});
