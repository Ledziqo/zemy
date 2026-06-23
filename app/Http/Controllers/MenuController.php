<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Support\GuestVisitManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MenuController extends Controller
{
    private const PUBLIC_MENU_CACHE_SECONDS = 60;

    public function show(Request $request, GuestVisitManager $visits, string $restaurant_slug, string $table_number)
    {
        [$restaurant, $restaurantTable] = $this->publicMenuPayload($restaurant_slug, $table_number);

        $visit = $visits->current($request, $restaurant, $restaurantTable);

        if ($visit) {
            $visits->touch($visit);
            $visit->load([
                'orders.items',
                'serviceRequests' => fn ($query) => $query->latest(),
                'payments' => fn ($query) => $query->latest(),
            ]);
        }

        return response()->view('menu.show', [
            'restaurant' => $restaurant,
            'table' => $restaurantTable,
            'categories' => $restaurant->categories->where('is_active', true),
            'visit' => $visit,
        ]);
    }

    public function confirmation(Request $request, GuestVisitManager $visits, string $restaurant_slug, string $table_number)
    {
        [$restaurant, $restaurantTable] = $this->publicMenuPayload($restaurant_slug, $table_number);
        $visit = $visits->current($request, $restaurant, $restaurantTable);
        if ($visit) {
            $visits->touch($visit);
            $visit->load('orders.items');
        }
        $table = $table_number;
        return response()->view('menu.confirmation', compact('restaurant', 'table', 'visit'));
    }

    private function publicMenuPayload(string $restaurantSlug, string $tableNumber): array
    {
        $version = (int) Restaurant::where('slug', $restaurantSlug)
            ->where('is_active', true)
            ->value('menu_cache_version') ?: 1;

        return Cache::remember(
            "public_menu:{$restaurantSlug}:v{$version}:{$tableNumber}",
            now()->addSeconds(self::PUBLIC_MENU_CACHE_SECONDS),
            function () use ($restaurantSlug, $tableNumber) {
                $restaurant = Restaurant::where('slug', $restaurantSlug)->where('is_active', true)
                    ->with([
                        'categories' => fn ($query) => $query->where('is_active', true),
                        'categories.menuItems' => fn ($query) => $query
                            ->orderBy('sort_order')
                            ->orderBy('id'),
                    ])
                    ->firstOrFail();

                $restaurantTable = $restaurant->tables()
                    ->where('table_number', $tableNumber)
                    ->where('is_active', true)
                    ->firstOrFail();

                return [$restaurant, $restaurantTable];
            }
        );
    }
}
