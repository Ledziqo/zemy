<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ImageOptimizer
{
    public static function createMenuDerivatives(string $sourcePath, string $filename, int $maxDimension = 480, int $quality = 65): bool
    {
        if (! is_file($sourcePath) || ! function_exists('imagecreatefromstring')) {
            return false;
        }

        $source = @file_get_contents($sourcePath);
        $image = $source !== false ? @imagecreatefromstring($source) : false;
        if (! $image) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxDimension / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        // Keep the alpha channel while resizing transparent logos and menu art.
        // Blending onto the new canvas first can turn transparent pixels into
        // a solid black/white block when the result is saved as WebP.
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $white);
        imagecopyresampled($target, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $directory = public_path('uploads/menu-items/optimized');
        if (! is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        $base = pathinfo($filename, PATHINFO_FILENAME);
        $savedWebp = ! function_exists('imagewebp') || imagewebp($target, $directory.DIRECTORY_SEPARATOR.$base.'.webp', $quality);
        $savedJpeg = ! function_exists('imagejpeg') || imagejpeg($target, $directory.DIRECTORY_SEPARATOR.$base.'.jpg', 82);

        imagedestroy($image);
        imagedestroy($target);

        return is_dir($directory) && $savedWebp && $savedJpeg;
    }

    public static function storeUpload(UploadedFile $file, string $folder, int $maxDimension = 1200, int $quality = 82): string
    {
        $directory = public_path('uploads/'.$folder);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $source = @file_get_contents($file->getRealPath());
        $image = $source !== false && function_exists('imagecreatefromstring') ? @imagecreatefromstring($source) : false;

        if (! $image) {
            $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
            $file->move($directory, $filename);
            return 'uploads/'.$folder.'/'.$filename;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxDimension / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        // QR logos may be transparent PNGs. Keep the destination canvas in
        // alpha mode while resampling so saving the uploaded logo does not
        // turn its transparent area into a black or white rectangle.
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);

        imagecopyresampled($target, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagesavealpha($target, true);

        $filename = Str::uuid().'.webp';
        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        $saved = function_exists('imagewebp') && imagewebp($target, $path, $quality);

        if ($saved && $folder === 'menu-items') {
            $fallbackDirectory = $directory.DIRECTORY_SEPARATOR.'optimized';
            if (! is_dir($fallbackDirectory)) {
                @mkdir($fallbackDirectory, 0755, true);
            }
            if (is_dir($fallbackDirectory) && function_exists('imagejpeg')) {
                imagejpeg($target, $fallbackDirectory.DIRECTORY_SEPARATOR.pathinfo($filename, PATHINFO_FILENAME).'.jpg', 82);
            }
        }

        imagedestroy($image);
        imagedestroy($target);

        if (! $saved) {
            $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
            $file->move($directory, $filename);
        }

        return 'uploads/'.$folder.'/'.$filename;
    }

    public static function storeDataUrl(string $dataUrl, string $folder, int $maxDimension = 1200, int $quality = 82): ?string
    {
        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $dataUrl)) {
            return null;
        }

        $base64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $binary = base64_decode($base64, true);

        if ($binary === false || strlen($binary) > 4 * 1024 * 1024 || @getimagesizefromstring($binary) === false) {
            return null;
        }

        $temp = tempnam(sys_get_temp_dir(), 'zemtab-image-');
        file_put_contents($temp, $binary);

        $upload = new UploadedFile($temp, 'image.png', null, null, true);
        $path = self::storeUpload($upload, $folder, $maxDimension, $quality);
        @unlink($temp);

        return $path;
    }
}
