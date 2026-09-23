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
            return self::assetUrl($optimizedFallback);
        }

        return self::assetUrl(Str::startsWith($path, 'uploads/') ? $path : 'storage/'.$path);
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
            return self::assetUrl($optimizedWebp);
        }

        return Str::endsWith(strtolower($path), '.webp')
            ? self::assetUrl(Str::startsWith($path, 'uploads/') ? $path : 'storage/'.$path)
            : null;
    }

    private static function assetUrl(string $path): string
    {
        $version = is_file(public_path($path)) ? (string) filemtime(public_path($path)) : null;

        return asset($path).($version ? '?v='.$version : '');
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
