<?php

namespace App\Support;

use App\Models\Restaurant;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class QrSetupPackStore
{
    private const BATCH_SIZE = 12;
    private const MAX_PAGES = 1000;
    private const MAX_PACK_BYTES = 30_000_000;

    public static function begin(Restaurant $restaurant, int $tableCount): string
    {
        $parent = storage_path('app/qr-setup-pack-builds/'.(int) $restaurant->id);
        if (is_dir($parent)) {
            foreach (File::directories($parent) as $candidate) {
                if (preg_match('/^[A-Za-z0-9]{48}$/', basename($candidate)) === 1
                    && (filemtime($candidate) ?: time()) < now()->subDay()->timestamp) {
                    File::deleteDirectory($candidate);
                }
            }
        }

        $token = Str::random(48);
        $directory = self::buildDirectory((int) $restaurant->id, $token);
        File::ensureDirectoryExists($directory);
        File::put($directory.'/manifest.json', json_encode([
            'fingerprint' => self::fingerprint($restaurant),
            'pages' => (int) ceil($tableCount / self::BATCH_SIZE),
        ], JSON_THROW_ON_ERROR));

        return $token;
    }

    public static function storePage(Restaurant $restaurant, string $token, int $page, string $html): void
    {
        abort_unless(self::validToken($token) && $page >= 0 && $page < self::MAX_PAGES, 422);
        $directory = self::buildDirectory((int) $restaurant->id, $token);
        abort_unless(is_file($directory.'/manifest.json'), 404);
        File::put($directory.'/page-'.str_pad((string) $page, 5, '0', STR_PAD_LEFT).'.html', $html);
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
        File::put($currentPath, json_encode(['token' => $token], JSON_THROW_ON_ERROR), true);

        if (is_array($old) && self::validToken((string) ($old['token'] ?? '')) && $old['token'] !== $token) {
            File::delete($readyDirectory.'/'.$old['token'].'.html');
        }
        File::deleteDirectory($buildDirectory);

        return self::signedUrl((int) $restaurant->id, $token);
    }

    public static function currentUrl(int $restaurantId): ?string
    {
        $directory = self::readyDirectory($restaurantId);
        $manifest = $directory.'/current.json';
        if (! is_file($manifest)) {
            return null;
        }

        $token = json_decode((string) File::get($manifest), true)['token'] ?? null;
        if (! is_string($token) || ! self::validToken($token) || ! is_file($directory.'/'.$token.'.html')) {
            return null;
        }

        return self::signedUrl($restaurantId, $token);
    }

    public static function invalidate(int $restaurantId): void
    {
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

        $currentToken = json_decode((string) File::get($manifestPath), true)['token'] ?? null;
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

    private static function fingerprint(Restaurant $restaurant): string
    {
        $tableState = \App\Models\RestaurantTable::query()
            ->where('restaurant_id', $restaurant->id)
            ->orderBy('id')
            ->get(['id', 'table_number', 'table_name', 'location_type', 'is_active', 'updated_at'])
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
