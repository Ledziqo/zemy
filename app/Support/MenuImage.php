<?php

namespace App\Support;

use Illuminate\Support\Str;

final class MenuImage
{
    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $optimizedFallback = self::optimizedPath($path, 'jpg');
        if ($optimizedFallback) {
            return asset($optimizedFallback);
        }

        return asset(Str::startsWith($path, 'uploads/') ? $path : 'storage/'.$path);
    }

    public static function webpUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return Str::endsWith(strtolower($path), '.webp') ? $path : null;
        }

        $optimizedWebp = self::optimizedPath($path, 'webp');
        if ($optimizedWebp) {
            return asset($optimizedWebp);
        }

        return Str::endsWith(strtolower($path), '.webp')
            ? asset(Str::startsWith($path, 'uploads/') ? $path : 'storage/'.$path)
            : null;
    }

    private static function optimizedPath(string $path, string $extension): ?string
    {
        if (! Str::startsWith($path, 'uploads/menu-items/')) {
            return null;
        }

        $filename = pathinfo($path, PATHINFO_FILENAME);
        if ($filename === '') {
            return null;
        }

        $candidate = 'uploads/menu-items/optimized/'.$filename.'.'.$extension;
        return is_file(public_path($candidate)) ? $candidate : null;
    }
}
