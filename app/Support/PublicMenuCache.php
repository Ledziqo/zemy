<?php

namespace App\Support;

use App\Models\Restaurant;

class PublicMenuCache
{
    public static function bump(Restaurant $restaurant): void
    {
        $restaurant->forceFill([
            'menu_cache_version' => ((int) ($restaurant->menu_cache_version ?? 1)) + 1,
        ])->save();
    }
}
