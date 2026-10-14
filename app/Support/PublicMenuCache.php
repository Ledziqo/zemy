<?php

namespace App\Support;

use App\Models\Restaurant;
use Illuminate\Support\Facades\Cache;

class PublicMenuCache
{
    public static function catalogUrl(Restaurant $restaurant): string
    {
        return \Illuminate\Support\Facades\Route::has('menu.catalog')
            ? route('menu.catalog', $restaurant->slug)
            : route('menu.show', [$restaurant->slug, '__zemtab_menu__']);
    }

    public static function informationUrl(Restaurant $restaurant): string
    {
        return self::catalogUrl($restaurant).'?information=1';
    }

    public static function bump(Restaurant $restaurant): void
    {
        Cache::store('file')->forget(self::payloadKey($restaurant->slug));
        Cache::store('file')->put(self::modeKey((int) $restaurant->id), $restaurant->isMenuOnly(), now()->addHours(12));
    }

    public static function modeKey(int $restaurantId): string
    {
        return 'restaurant_menu_only:'.$restaurantId;
    }

    public static function isMenuOnly(int $restaurantId): bool
    {
        return Cache::store('file')->remember(self::modeKey($restaurantId), now()->addHours(12),
            fn () => Restaurant::findOrFail($restaurantId)->isMenuOnly());
    }

    public static function payloadKey(string $slug): string
    {
        return "public_menu_payload:{$slug}";
    }
}
