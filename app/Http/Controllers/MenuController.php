<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Support\PublicMenuCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MenuController extends Controller
{
    private const PUBLIC_MENU_CACHE_SECONDS = 43200;

    public function show(Request $request, string $restaurant_slug, string $table_number)
    {
        [$restaurant, $restaurantTable] = $this->publicMenuPayload($restaurant_slug, $table_number);

        return response()->view('menu.show', [
            'restaurant' => $restaurant,
            'table' => $restaurantTable,
            'categories' => $restaurant->categories->where('is_active', true),
        ])->header('Cache-Control', 'private, no-cache, must-revalidate')
            ->header('Vary', 'Cookie, Accept-Encoding');
    }

    public function confirmation(Request $request, string $restaurant_slug, string $table_number)
    {
        [$restaurant, $restaurantTable] = $this->publicMenuPayload($restaurant_slug, $table_number);
        $table = $table_number;
        return response()->view('menu.confirmation', compact('restaurant', 'table'))
            ->header('Cache-Control', 'no-store');
    }

    private function publicMenuPayload(string $restaurantSlug, string $tableNumber): array
    {
        $restaurant = Cache::store('file')->remember(
            PublicMenuCache::payloadKey($restaurantSlug),
            now()->addSeconds(self::PUBLIC_MENU_CACHE_SECONDS),
            fn () => Restaurant::where('slug', $restaurantSlug)->where('is_active', true)
                ->with([
                    'categories' => fn ($query) => $query->where('is_active', true),
                    'categories.menuItems' => fn ($query) => $query
                        ->orderBy('sort_order')
                        ->orderBy('id'),
                    'tables' => fn ($query) => $query->where('is_active', true),
                ])
                ->firstOrFail()
        );

        $restaurantTable = $restaurant->tables->firstWhere('table_number', $tableNumber);
        abort_unless($restaurantTable, 404);
        $restaurantTable->setRelation('restaurant', $restaurant);

        return [$restaurant, $restaurantTable];
    }
}
