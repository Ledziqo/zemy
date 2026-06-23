<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ImageOptimizer
{
    public static function storeUpload(UploadedFile $file, string $folder, int $maxDimension = 1200): string
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
        imagealphablending($target, true);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);

        imagecopyresampled($target, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $filename = Str::uuid().'.webp';
        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        $saved = function_exists('imagewebp') && imagewebp($target, $path, 82);

        imagedestroy($image);
        imagedestroy($target);

        if (! $saved) {
            $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
            $file->move($directory, $filename);
        }

        return 'uploads/'.$folder.'/'.$filename;
    }

    public static function storeDataUrl(string $dataUrl, string $folder, int $maxDimension = 1200): ?string
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
        $path = self::storeUpload($upload, $folder, $maxDimension);
        @unlink($temp);

        return $path;
    }
}
