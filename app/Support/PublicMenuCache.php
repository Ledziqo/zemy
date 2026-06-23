<?php

namespace App\Support;

use App\Models\Restaurant;
use Illuminate\Support\Facades\Cache;

class PublicMenuCache
{
    public const VERSION_CACHE_SECONDS = 300;

    public static function versionForSlug(string $slug): int
    {
        return (int) Cache::remember(
            self::versionKey($slug),
            now()->addSeconds(self::VERSION_CACHE_SECONDS),
            fn () => Restaurant::where('slug', $slug)->where('is_active', true)->value('menu_cache_version') ?: 1
        );
    }

    public static function bump(Restaurant $restaurant): void
    {
        $restaurant->forceFill([
            'menu_cache_version' => ((int) ($restaurant->menu_cache_version ?? 1)) + 1,
        ])->save();

        Cache::forget(self::versionKey($restaurant->slug));
    }

    private static function versionKey(string $slug): string
    {
        return "public_menu_version:{$slug}";
    }
}
