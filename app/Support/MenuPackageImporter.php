<?php

namespace App\Support;

use App\Models\Restaurant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

final class MenuPackageImporter
{
    private const MAX_UNCOMPRESSED_BYTES = 512 * 1024 * 1024;
    private const MAX_ITEMS = 500;

    public function import(Restaurant $restaurant, UploadedFile $package): array
    {
        $workDir = storage_path('app/menu-imports/'.Str::uuid());
        if (! is_dir($workDir) && ! File::makeDirectory($workDir, 0755, true, true) && ! is_dir($workDir)) {
            throw new RuntimeException('The menu import workspace could not be created.');
        }
        $copiedFiles = [];
        $committed = false;

        try {
            $manifestPath = $this->extractPackage($package, $workDir);
            $manifest = json_decode(File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            $rows = $this->validateManifest($manifest, $workDir);

            $menuDirectory = public_path('uploads/menu-items');
            if (! is_dir($menuDirectory) && ! File::makeDirectory($menuDirectory, 0755, true, true) && ! is_dir($menuDirectory)) {
                throw new RuntimeException('The menu image directory could not be created.');
            }
            foreach ($rows['items'] as $index => $row) {
                $extension = strtolower(pathinfo($row['source_image'], PATHINFO_EXTENSION));
                $filename = Str::slug($row['category_key'].'-'.$row['name']).'-'.substr($row['image_hash'], 0, 10).'.'.$extension;
                $destination = public_path('uploads/menu-items/'.$filename);

                if (! File::exists($destination)) {
                    File::copy($row['source_image'], $destination);
                    $copiedFiles[] = $destination;
                }
                ImageOptimizer::createMenuDerivatives($destination, $filename, 480, 65);

                $rows['items'][$index]['stored_image_path'] = 'uploads/menu-items/'.$filename;
            }

            $hasImageSource = Schema::hasColumn('menu_items', 'image_source_url');
            DB::transaction(function () use ($restaurant, $rows, $hasImageSource): void {
                DB::table('menu_items')->where('restaurant_id', $restaurant->id)->delete();
                DB::table('categories')->where('restaurant_id', $restaurant->id)->delete();

                $categoryIds = [];
                foreach ($rows['categories'] as $category) {
                    $categoryIds[$category['name_key']] = DB::table('categories')->insertGetId([
                        'restaurant_id' => $restaurant->id,
                        'name' => $category['name'],
                        'sort_order' => $category['sort_order'],
                        'is_active' => $category['is_active'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                foreach ($rows['items'] as $item) {
                    $values = [
                        'restaurant_id' => $restaurant->id,
                        'category_id' => $categoryIds[$item['category_key']],
                        'name' => $item['name'],
                        'description' => $item['description'],
                        'price' => $item['price'],
                        'image_path' => $item['stored_image_path'],
                        'is_available' => $item['is_available'],
                        'is_featured' => $item['is_featured'],
                        'sort_order' => $item['sort_order'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    if ($hasImageSource) {
                        $values['image_source_url'] = $item['image_source_url'];
                    }
                    DB::table('menu_items')->insert($values);
                }
            });

            PublicMenuCache::bump($restaurant->fresh());
            $committed = true;

            return [
                'categories' => count($rows['categories']),
                'items' => count($rows['items']),
                'images' => count($rows['items']),
            ];
        } finally {
            if (! $committed) {
                foreach ($copiedFiles as $path) {
                    @unlink($path);
                }
            }
            File::deleteDirectory($workDir);
        }
    }

    private function extractPackage(UploadedFile $package, string $workDir): string
    {
        $zip = new ZipArchive();
        if ($zip->open($package->getRealPath()) !== true) {
            throw new RuntimeException('The menu package is not a readable ZIP file.');
        }

        $totalBytes = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            $name = str_replace('\\', '/', (string) ($stat['name'] ?? ''));
            if ($name === '' || str_starts_with($name, '/') || preg_match('/(^|\/)\.\.?($|\/)/', $name) || preg_match('/^[A-Za-z]:/', $name)) {
                $zip->close();
                throw new RuntimeException('The menu package contains an unsafe file path.');
            }
            $totalBytes += (int) ($stat['size'] ?? 0);
            if ($totalBytes > self::MAX_UNCOMPRESSED_BYTES) {
                $zip->close();
                throw new RuntimeException('The menu package is too large after extraction.');
            }
        }

        if ($zip->locateName('menu.json', ZipArchive::FL_NODIR) === false) {
            $zip->close();
            throw new RuntimeException('The ZIP must contain a root-level menu.json file.');
        }
        if (! $zip->extractTo($workDir)) {
            $zip->close();
            throw new RuntimeException('The menu package could not be extracted.');
        }
        $zip->close();

        return $workDir.'/menu.json';
    }

    private function validateManifest(array $manifest, string $workDir): array
    {
        if (($manifest['format'] ?? null) !== 'zemtab-menu' || (int) ($manifest['version'] ?? 0) !== 1) {
            throw new RuntimeException('This is not a supported ZemTab menu package.');
        }

        $categories = $manifest['categories'] ?? null;
        if (! is_array($categories) || $categories === []) {
            throw new RuntimeException('The menu package must contain at least one category.');
        }

        $categoryRows = [];
        $itemRows = [];
        $usedItemKeys = [];
        $usedImageHashes = [];

        foreach (array_values($categories) as $categoryIndex => $category) {
            $categoryName = trim((string) ($category['name'] ?? ''));
            $categoryKey = Str::lower($categoryName);
            if ($categoryName === '' || mb_strlen($categoryName) > 255 || isset($categoryRows[$categoryKey])) {
                throw new RuntimeException('Each category must have a unique, non-empty name.');
            }

            $categoryRows[$categoryKey] = [
                'name' => $categoryName,
                'name_key' => $categoryKey,
                'sort_order' => (int) ($category['sort_order'] ?? (($categoryIndex + 1) * 10)),
                'is_active' => array_key_exists('is_active', $category) ? (bool) $category['is_active'] : true,
            ];

            if (! is_array($category['items'] ?? null) || $category['items'] === []) {
                throw new RuntimeException("Category '{$categoryName}' has no items.");
            }

            foreach (array_values($category['items']) as $itemIndex => $item) {
                if (count($itemRows) >= self::MAX_ITEMS) {
                    throw new RuntimeException('The menu package contains too many items.');
                }

                $name = trim((string) ($item['name'] ?? ''));
                $itemKey = $categoryKey.'|'.Str::lower($name);
                if ($name === '' || mb_strlen($name) > 255 || isset($usedItemKeys[$itemKey])) {
                    throw new RuntimeException("Category '{$categoryName}' contains a duplicate or invalid item name.");
                }
                if (! isset($item['price']) || ! is_numeric($item['price']) || (float) $item['price'] < 0) {
                    throw new RuntimeException("Item '{$name}' must have a valid non-negative price.");
                }

                $sourceImage = $this->safePath($workDir, (string) ($item['image'] ?? ''));
                $imageInfo = @getimagesize($sourceImage);
                if (! $imageInfo || ! in_array($imageInfo['mime'] ?? '', ['image/jpeg', 'image/png', 'image/webp'], true)) {
                    throw new RuntimeException("Item '{$name}' does not have a valid JPG, PNG, or WebP image.");
                }
                if (filesize($sourceImage) > 12 * 1024 * 1024) {
                    throw new RuntimeException("Image for '{$name}' is larger than 12 MB.");
                }

                $imageHash = sha1_file($sourceImage);
                if (isset($usedImageHashes[$imageHash])) {
                    throw new RuntimeException("The image for '{$name}' is repeated. Every menu item must have a unique photo.");
                }

                $usedItemKeys[$itemKey] = true;
                $usedImageHashes[$imageHash] = true;
                $itemRows[] = [
                    'category_key' => $categoryKey,
                    'name' => $name,
                    'description' => trim((string) ($item['description'] ?? '')),
                    'price' => round((float) $item['price'], 2),
                    'image_path' => (string) $item['image'],
                    'source_image' => $sourceImage,
                    'image_hash' => $imageHash,
                    'image_source_url' => isset($item['image_source_url']) ? (string) $item['image_source_url'] : null,
                    'is_available' => array_key_exists('is_available', $item) ? (bool) $item['is_available'] : true,
                    'is_featured' => array_key_exists('is_featured', $item) ? (bool) $item['is_featured'] : false,
                    'sort_order' => (int) ($item['sort_order'] ?? (($itemIndex + 1) * 10)),
                ];
            }
        }

        return ['categories' => array_values($categoryRows), 'items' => $itemRows];
    }

    private function safePath(string $baseDir, string $relativePath): string
    {
        $relativePath = str_replace('\\', '/', trim($relativePath));
        if ($relativePath === '' || str_starts_with($relativePath, '/') || preg_match('/(^|\/)\.\.?($|\/)/', $relativePath) || preg_match('/^[A-Za-z]:/', $relativePath)) {
            throw new RuntimeException('The menu package contains an unsafe image path.');
        }

        $base = realpath($baseDir);
        $path = realpath($baseDir.DIRECTORY_SEPARATOR.$relativePath);
        if (! $base || ! $path || ! str_starts_with($path, $base.DIRECTORY_SEPARATOR) || ! is_file($path)) {
            throw new RuntimeException("The package image '{$relativePath}' is missing.");
        }

        return $path;
    }
}
