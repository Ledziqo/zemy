<?php

use App\Http\Middleware\EnsureRestaurantDashboardAccess;
use App\Http\Middleware\EnsureStaffProfileSelected;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'restaurant.access' => EnsureRestaurantDashboardAccess::class,
            'role' => RoleMiddleware::class,
            'locale' => SetLocale::class,
            'profile.selected' => EnsureStaffProfileSelected::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $exception, Request $request) {
            $message = strtolower($exception->getMessage());
            if (! str_contains($message, 'max_connections_per_hour') && ! str_contains($message, '[1226]')) {
                return null;
            }

            report($exception);
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The database is temporarily rate-limited. We could not verify completion; retry shortly. Duplicate order submits are protected.',
                    'retryable' => true,
                ], 503)->header('Retry-After', '60');
            }

            $slug = $request->route('restaurant_slug');
            $table = $request->route('table_number');
            $retryUrl = $slug && $table ? route('menu.show', [$slug, $table]) : route('login');
            return response()->view('errors.database-throttled', ['retryUrl' => $retryUrl], 503)->header('Retry-After', '60');
        });
    })->create();
