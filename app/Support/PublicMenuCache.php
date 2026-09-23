<?php

namespace App\Support;

use App\Models\Restaurant;
use Illuminate\Support\Facades\Cache;

class PublicMenuCache
{
    public static function bump(Restaurant $restaurant): void
    {
        Cache::store('file')->forget(self::payloadKey($restaurant->slug));
    }

    public static function payloadKey(string $slug): string
    {
        return "public_menu_payload:{$slug}";
    }
}
