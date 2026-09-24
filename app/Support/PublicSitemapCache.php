<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class PublicSitemapCache
{
    public static function key(): string
    {
        return 'public_sitemap_xml';
    }

    public static function forget(): void
    {
        Cache::store('file')->forget(self::key());
    }
}
