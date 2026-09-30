<?php

namespace App\Support;

use App\Models\Restaurant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class QrSetupPackStore
{
    private const BATCH_SIZE = 8;
    private const PRINT_LAYOUT_VERSION = 14;
    private const MAX_PAGES = 1000;
    private const MAX_PACK_BYTES = 30_000_000;

    public static function begin(Restaurant $restaurant, Collection $tables, int $pageCount): string
    {
        $parent = storage_path('app/qr-setup-pack-builds/'.(int) $restaurant->id);
        File::ensureDirectoryExists($parent);
        if (is_dir($parent)) {
            foreach (File::directories($parent) as $candidate) {
                if (preg_match('/^[A-Za-z0-9]{48}$/', basename($candidate)) === 1
                    && (filemtime($candidate) ?: time()) < now()->subDay()->timestamp) {
                    File::deleteDirectory($candidate);
                }
            }
        }

        $lock = fopen($parent.'/active-build.lock', 'c+');
        abort_unless($lock !== false, 500, 'Could not lock QR print-pack builder.');
        flock($lock, LOCK_EX);
        try {
            $fingerprint = self::fingerprint($restaurant, $tables);
            $activeTables = $tables->filter(fn ($table) => (bool) $table->is_active)->values();
            $activePath = $parent.'/active-build.json';
            $active = is_file($activePath) ? json_decode((string) File::get($activePath), true) : null;
            $activeToken = is_array($active) ? ($active['token'] ?? null) : null;
            if (is_string($activeToken) && self::validToken($activeToken)) {
                $activeDirectory = self::buildDirectory((int) $restaurant->id, $activeToken);
                $manifestPath = $activeDirectory.'/manifest.json';
                $manifest = is_file($manifestPath) ? json_decode((string) File::get($manifestPath), true) : null;
                $expectedPages = $pageCount;
                if (is_array($manifest)
                    && ($manifest['layout_version'] ?? null) === self::PRINT_LAYOUT_VERSION
                    && hash_equals((string) ($manifest['fingerprint'] ?? ''), $fingerprint)
                    && (int) ($manifest['pages'] ?? -1) === $expectedPages
                    && (int) ($manifest['created_at'] ?? 0) >= now()->subMinutes(30)->timestamp
                    && is_file($activeDirectory.'/snapshot.json')) {
                    return $activeToken;
                }
                File::delete($activePath);
            }

            $token = Str::random(48);
            $directory = self::buildDirectory((int) $restaurant->id, $token);
            File::ensureDirectoryExists($directory);
            File::put($directory.'/manifest.json', json_encode([
                'fingerprint' => $fingerprint,
                'pages' => $pageCount,
                'created_at' => now()->timestamp,
                'layout_version' => self::PRINT_LAYOUT_VERSION,
            ], JSON_THROW_ON_ERROR));
            File::put($directory.'/snapshot.json', json_encode([
                'restaurant' => array_intersect_key($restaurant->getAttributes(), array_flip([
                    'id', 'name', 'slug', 'business_type', 'primary_color', 'logo_path', 'settings', 'updated_at',
                ])),
                'tables' => $activeTables->map(fn ($table) => array_intersect_key($table->getAttributes(), array_flip([
                    'id', 'restaurant_id', 'table_number', 'table_name', 'location_type', 'is_active', 'updated_at',
                ])))->values()->all(),
            ], JSON_THROW_ON_ERROR));
            File::put($activePath, json_encode(['token' => $token], JSON_THROW_ON_ERROR), true);

            return $token;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public static function snapshot(int $restaurantId, string $token): ?array
    {
        if (! self::validToken($token)) {
            return null;
        }

        $directory = self::buildDirectory($restaurantId, $token);
        $manifestPath = $directory.'/manifest.json';
        $snapshotPath = $directory.'/snapshot.json';
        if (! is_file($manifestPath) || ! is_file($snapshotPath)) {
            return null;
        }

        $manifest = json_decode((string) File::get($manifestPath), true);
        $snapshot = json_decode((string) File::get($snapshotPath), true);
        return is_array($manifest)
            && ($manifest['layout_version'] ?? null) === self::PRINT_LAYOUT_VERSION
            && is_array($snapshot)
            && is_array($snapshot['restaurant'] ?? null)
            && is_array($snapshot['tables'] ?? null)
                ? $snapshot
                : null;
    }

    public static function renderPageOnce(int $restaurantId, string $token, int $page, callable $render): ?string
    {
        if (! self::validToken($token) || $page < 0 || $page >= self::MAX_PAGES) {
            return null;
        }

        $directory = self::buildDirectory($restaurantId, $token);
        $manifestPath = $directory.'/manifest.json';
        if (! is_file($manifestPath)) {
            return null;
        }
        $manifest = json_decode((string) File::get($manifestPath), true);
        if (! is_array($manifest) || $page >= (int) ($manifest['pages'] ?? 0)) {
            return null;
        }

        $path = $directory.'/page-'.str_pad((string) $page, 5, '0', STR_PAD_LEFT).'.html';
        $lock = fopen($path.'.lock', 'c+');
        if ($lock === false) {
            return null;
        }

        flock($lock, LOCK_EX);
        try {
            if (is_file($path)) {
                return File::get($path);
            }

            $html = $render();
            File::put($path, $html, true);
            return $html;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public static function publish(Restaurant $restaurant, string $token, string $shell, int $pageCount): string
    {
        abort_unless(self::validToken($token), 422);
        $buildDirectory = self::buildDirectory((int) $restaurant->id, $token);
        $manifestPath = $buildDirectory.'/manifest.json';
        abort_unless(is_file($manifestPath), 404, 'This QR pack build has expired. Start again.');

        $manifest = json_decode((string) File::get($manifestPath), true);
        abort_unless(is_array($manifest) && hash_equals((string) ($manifest['fingerprint'] ?? ''), self::fingerprint($restaurant)), 409, 'Tables or QR design changed while this pack was being prepared. Start again to include the latest changes.');
        abort_unless($pageCount > 0 && $pageCount === (int) ($manifest['pages'] ?? -1) && str_contains($shell, '<!--QR_PACK_PAGES-->'), 422, 'The QR pack is incomplete. Start again.');

        $pages = '';
        $bytes = strlen($shell);
        for ($page = 0; $page < $pageCount; $page++) {
            $path = $buildDirectory.'/page-'.str_pad((string) $page, 5, '0', STR_PAD_LEFT).'.html';
            abort_unless(is_file($path), 422, 'A QR page is missing. Start again.');
            $bytes += filesize($path) ?: 0;
            abort_if($bytes > self::MAX_PACK_BYTES, 413, 'This QR pack is too large to prepare as one print file.');
            $pages .= File::get($path);
        }

        // Only server-rendered card markup is persisted. Discard builder scripts
        // and all event handlers, then attach one fixed print action.
        $shell = preg_replace('#<script\b[^>]*>.*?</script\s*>#is', '', $shell) ?? $shell;
        $shell = preg_replace('/\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $shell) ?? $shell;
        $shell = preg_replace('/<button\b(?=[^>]*\bid="print-pack-button")[^>]*>/i', '<button type="button" id="print-pack-button" disabled>', $shell, 1) ?? $shell;
        $shell = str_replace('<!--QR_PACK_PAGES-->', $pages, $shell);
        $shell = str_ireplace('</body>', '<script>window.addEventListener("load",()=>{const b=document.getElementById("print-pack-button");if(!b)return;b.disabled=false;b.addEventListener("click",async()=>{const images=[...document.images];try{await Promise.all(images.map(i=>i.decode()));window.print()}catch(e){alert("A QR or logo image could not load. Reload before printing.")}})});</script></body>', $shell);

        $readyDirectory = self::readyDirectory((int) $restaurant->id);
        File::ensureDirectoryExists($readyDirectory);
        $currentPath = $readyDirectory.'/current.json';
        $old = is_file($currentPath) ? json_decode((string) File::get($currentPath), true) : null;
        $fileName = $token.'.html';
        $temporaryPath = $readyDirectory.'/'.$fileName.'.tmp';
        File::put($temporaryPath, $shell);
        File::move($temporaryPath, $readyDirectory.'/'.$fileName);
        File::put($currentPath, json_encode([
            'token' => $token,
            'layout_version' => self::PRINT_LAYOUT_VERSION,
        ], JSON_THROW_ON_ERROR), true);

        $activePath = dirname($buildDirectory).'/active-build.json';
        $active = is_file($activePath) ? json_decode((string) File::get($activePath), true) : null;
        if (is_array($active) && ($active['token'] ?? null) === $token) {
            File::delete($activePath);
        }

        if (is_array($old) && self::validToken((string) ($old['token'] ?? '')) && $old['token'] !== $token) {
            File::delete($readyDirectory.'/'.$old['token'].'.html');
        }
        return self::signedUrl((int) $restaurant->id, $token);
    }

    public static function currentUrl(int $restaurantId): ?string
    {
        $directory = self::readyDirectory($restaurantId);
        $manifest = $directory.'/current.json';
        if (! is_file($manifest)) {
            return null;
        }

        $current = json_decode((string) File::get($manifest), true);
        if (! is_array($current) || ($current['layout_version'] ?? null) !== self::PRINT_LAYOUT_VERSION) {
            self::invalidate($restaurantId);
            return null;
        }

        $token = $current['token'] ?? null;
        if (! is_string($token) || ! self::validToken($token) || ! is_file($directory.'/'.$token.'.html')) {
            return null;
        }

        return self::signedUrl($restaurantId, $token);
    }

    public static function invalidate(int $restaurantId): void
    {
        File::delete(storage_path('app/qr-setup-pack-builds/'.$restaurantId.'/active-build.json'));
        $directory = self::readyDirectory($restaurantId);
        $manifest = $directory.'/current.json';
        if (is_file($manifest)) {
            $token = json_decode((string) File::get($manifest), true)['token'] ?? null;
            if (is_string($token) && self::validToken($token)) {
                File::delete($directory.'/'.$token.'.html');
            }
            File::delete($manifest);
        }
    }

    public static function preparedHtml(int $restaurantId, string $token): ?string
    {
        if (! self::validToken($token)) {
            return null;
        }

        $directory = self::readyDirectory($restaurantId);
        $manifestPath = $directory.'/current.json';
        if (! is_file($manifestPath)) {
            return null;
        }

        $current = json_decode((string) File::get($manifestPath), true);
        if (! is_array($current) || ($current['layout_version'] ?? null) !== self::PRINT_LAYOUT_VERSION) {
            return null;
        }

        $currentToken = $current['token'] ?? null;
        $path = $directory.'/'.$token.'.html';
        return $currentToken === $token && is_file($path) ? File::get($path) : null;
    }

    private static function buildDirectory(int $restaurantId, string $token): string
    {
        abort_unless(self::validToken($token), 422);
        return storage_path('app/qr-setup-pack-builds/'.$restaurantId.'/'.$token);
    }

    private static function readyDirectory(int $restaurantId): string
    {
        return storage_path('app/qr-setup-pack-ready/'.$restaurantId);
    }

    private static function signedUrl(int $restaurantId, string $token): string
    {
        return URL::temporarySignedRoute('qr.print', now()->addMinutes(30), [
            'restaurantId' => $restaurantId,
            'token' => $token,
        ]);
    }

    private static function fingerprint(Restaurant $restaurant, ?Collection $tables = null): string
    {
        $tableState = ($tables ?? \App\Models\RestaurantTable::query()
            ->where('restaurant_id', $restaurant->id)
            ->orderBy('id')
            ->get(['id', 'table_number', 'table_name', 'location_type', 'is_active', 'updated_at']))
            ->sortBy('id')
            ->map(fn ($table) => [
                $table->id,
                $table->table_number,
                $table->table_name,
                $table->location_type,
                (bool) $table->is_active,
                (string) $table->updated_at,
            ])->all();

        return sha1(json_encode([
            (int) $restaurant->id,
            $restaurant->name,
            $restaurant->slug,
            $restaurant->business_type,
            $restaurant->primary_color,
            (string) $restaurant->updated_at,
            $restaurant->logo_path,
            $restaurant->settings['qr_sticker'] ?? [],
            $tableState,
        ], JSON_THROW_ON_ERROR));
    }

    private static function validToken(string $token): bool
    {
        return preg_match('/^[A-Za-z0-9]{48}$/', $token) === 1;
    }
}
