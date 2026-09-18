<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$migration = file_get_contents($root.'/database/migrations/2026_09_17_020000_refresh_tulip_menu_content.php');

function extractArray(string $source, string $variable): array
{
    $start = strpos($source, '$'.$variable.' = [');
    if ($start === false) {
        throw new RuntimeException("Could not find {$variable} in source.");
    }

    $open = strpos($source, '[', $start);
    $depth = 0;
    $single = false;
    $double = false;
    $escaped = false;

    for ($i = $open, $length = strlen($source); $i < $length; $i++) {
        $character = $source[$i];
        if (($single || $double) && $escaped) {
            $escaped = false;
            continue;
        }
        if (($single || $double) && $character === '\\') {
            $escaped = true;
            continue;
        }
        if (! $double && $character === "'") {
            $single = ! $single;
            continue;
        }
        if (! $single && $character === '"') {
            $double = ! $double;
            continue;
        }
        if ($single || $double) {
            continue;
        }
        if ($character === '[') {
            $depth++;
        } elseif ($character === ']') {
            $depth--;
            if ($depth === 0) {
                /** @var array $value */
                $value = eval('return '.substr($source, $open, $i - $open + 1).';');
                return $value;
            }
        }
    }

    throw new RuntimeException("Could not parse {$variable}.");
}

$catalog = extractArray($migration, 'catalog');
$specificDescriptions = extractArray($migration, 'specificDescriptions');

$prices = [
    'Hot Beverage (Cup)' => ['Coffee Americano' => 140, 'Café Latte' => 180, 'Coffee' => 140, 'Cappuccino' => 160, 'Double Espresso' => 160, 'Double Macchiato' => 180, 'Espresso' => 140, 'Hot Chocolate' => 180, 'Macchiato' => 150, 'Milk' => 160, 'Selection of Tea' => 100, 'Tea with Coffee' => 130, 'Tea with Milk' => 150, 'Special Tea' => 230, 'Tea with Honey' => 150, 'Traditional Coffee' => 130],
    'Beer (Bottle)' => ['St. George' => 190, 'Habesha' => 190, 'Castel' => 190, 'Dashan' => 190, 'Kagan' => 190, 'Heineken' => 220, 'Arad' => 190],
    'Soft Drinks (Bottle)' => ['Soft Drinks' => 110, 'Small Water (600 ml)' => 110, 'Medium Water (1000 ml)' => 140, 'Big Water (2000 ml)' => 180],
    'Wines' => ['Roberson (Bottle)' => 4900, 'Local Glass of Wine' => 880, 'Acacia Dry Red (Glass)' => 880, 'Acacia M.S. Rose (Glass)' => 880, 'Acacia M.S. Red (Glass)' => 880, 'Acacia M.S. White (Glass)' => 880, 'Acacia Medium Sweet White (Bottle)' => 3000, 'Acacia Medium Sweet Red (Bottle)' => 3000, 'Acacia Medium Sweet Rose (Bottle)' => 3000, 'Acacia Dry Red (Bottle)' => 3000, 'Rift Valley Merlot (Bottle)' => 3000, 'Rift Valley Chardonnay (Bottle)' => 3000, 'Rift Valley Syrah (Bottle)' => 3000, 'Rift Valley Dry Rosé (Bottle)' => 3000, 'Rift Valley Cabernet Sauvignon (Bottle)' => 3000],
    'Aperitif' => ['Campari Bitter' => [310, 6965], 'Fernet Bianca' => [330, 7085], 'Martini Bianco' => [310, 6965], 'Martini Extra Dry' => 310, 'Martini Rosso' => [310, 6965], 'Pastis 51' => [320, 7915]],
    'Gin' => ['Bombay Sapphire' => [500, 16270], 'Gordon’s' => [600, 17900], 'Tanqueray' => [380, 6160]],
    'Vodka' => ['Absolut Blue' => [470, 13630], 'Cîroc' => [455, 9730], 'Grey Goose' => [470, 10630], 'Smirnoff Red (750 ml)' => [360, 9100], 'Stolichnaya Red (750 ml)' => [360, 9100], 'Stolichnaya Red (50 cl)' => [360, 4050], 'Winter Palace (1000 ml)' => [320, 10400]],
    'Liquor' => ['Cointreau' => [400, 8930], 'Kahlúa' => [420, 5240], 'Sambuca (700 ml)' => [320, 6390], 'Tia Maria' => [320, 5050]],
    'Rum' => ['Bacardi' => [270, 5380], 'Bacardi White' => [270, 5380], 'Captain Morgan Black' => [580, 8650], 'Captain Morgan Gold' => [580, 8650], 'Havana Club (1000 ml)' => [360, 5500], 'Malibu' => [400, 11000]],
    'Regular Blended Whisky' => ['Johnnie Walker Double Black' => [1000, 35500], 'Johnnie Walker Black Label' => [880, 30000], 'Jack Daniel’s' => [880, 30000], 'J&B Rare' => [480, null]],
    'Deluxe Whisky' => ['Chivas Regal 12 Years' => [535, 20930], 'Chivas Regal 18 Years' => [null, 38000], 'Johnnie Walker Gold' => [null, 44155], 'Johnnie Walker Platinum' => [null, 34755], 'Johnnie Walker Blue' => [null, 68310]],
    'Single Malt Whisky' => ['Glenfiddich 12 Years' => [650, 25560], 'Glenfiddich 18 Years' => [null, 27800]],
    'Tequila' => ['Jose Cuervo Silver' => [371, 9100], 'Tequila Camino Silver' => [496, 10695], 'Tequila Camino Gold' => [496, 10695]],
    'Cognac & Brandy' => ['Courvoisier VS' => [null, 30000], 'Courvoisier VSOP' => [null, 30000]],
    'Pizza Corner' => ['Four Season Pizza' => 750, 'Vegetarian Pizza' => 700, 'Margarita Pizza' => 750, 'Tuna Pizza' => 700],
    'Fresh Healthy Organic Fruit Juice' => ['Orange Juice' => 350, 'Papaya' => 350, 'Watermelon' => 330, 'Mixed Juice' => 330, 'Avocado' => 350],
    'Cake Corner' => ['Carrot Cake' => 330, 'English Cake' => 200, 'Banana Cake' => 200, 'Soft Cake' => 200, 'Cookies (1 kg)' => 680, 'Torta Cake' => 2000],
];

function itemPrice(string $category, string $name, array $prices): ?float
{
    if (array_key_exists($name, $prices[$category] ?? [])) {
        $value = $prices[$category][$name];
        return is_array($value) ? (float) ($value[0] ?? 0) : (float) $value;
    }

    $baseName = preg_replace('/ \((Glass|Bottle)\)$/', '', $name);
    $value = $prices[$category][$baseName] ?? null;
    if (! is_array($value)) {
        return $value === null ? null : (float) $value;
    }

    return str_ends_with($name, ' (Bottle)')
        ? ($value[1] === null ? null : (float) $value[1])
        : ($value[0] === null ? null : (float) $value[0]);
}

$categories = [];
$usedHashes = [];
foreach ($catalog as $categoryIndex => $items) {
    $categoryRows = [];
    foreach ($items as $itemIndex => $image) {
        $price = itemPrice($categoryIndex, (string) $itemIndex, $prices);
        $isAvailable = $price !== null;
        $price ??= 0;

        $source = $root.'/public/uploads/menu-items/'.$image;
        if (! is_file($source)) {
            throw new RuntimeException("Missing menu image: {$source}");
        }
        $hash = sha1_file($source);
        if (isset($usedHashes[$hash])) {
            throw new RuntimeException("Repeated menu image: {$image}");
        }
        $usedHashes[$hash] = true;

        $baseName = preg_replace('/ \((Glass|Bottle)\)$/', '', (string) $itemIndex);
        $unit = str_ends_with((string) $itemIndex, ' (Glass)') ? ' Served by the glass.' : (str_ends_with((string) $itemIndex, ' (Bottle)') ? ' Served by the bottle.' : '');
        $description = isset($specificDescriptions[$baseName])
            ? $specificDescriptions[$baseName].$unit
            : match ($categoryIndex) {
                'Beer (Bottle)' => $baseName.' beer served chilled.',
                'Soft Drinks (Bottle)' => $itemIndex.' served chilled.',
                'Wines' => $unit === '' ? 'A bottle of '.$baseName.' wine.' : 'A serving of '.$baseName.' wine by the glass.',
                default => $baseName.' served as a '.($unit === '' ? 'house pour.' : strtolower(trim($unit))),
            };
        if (! $isAvailable) {
            $description .= ' Price to be confirmed by Tulip Olympia.';
        }

        $categoryRows[] = [
            'name' => $itemIndex,
            'description' => trim($description),
            'price' => $price,
            'image' => 'photos/'.$image,
            'is_available' => $isAvailable,
            'is_featured' => false,
            'sort_order' => (count($categoryRows) + 1) * 10,
        ];
    }
    $categories[] = [
        'name' => $categoryIndex,
        'sort_order' => (count($categories) + 1) * 10,
        'is_active' => true,
        'items' => $categoryRows,
    ];
}

$manifest = [
    'format' => 'zemtab-menu',
    'version' => 1,
    'restaurant_slug' => 'tulip-olympia',
    'restaurant_name' => 'Tulip Olympia',
    'generated_at' => date(DATE_ATOM),
    'categories' => $categories,
];

$outputDir = $root.'/output/menu';
if (! is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}
$zipPath = $outputDir.'/tulip-olympia-final-menu-v3.zip';
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Could not create the menu package.');
}
$zip->addFromString('menu.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
$zip->addFromString('README.txt', "ZemTab menu import package\n\nUpload this ZIP in Admin > Database > Replace menu from import package.\nIt replaces the selected restaurant menu categories and items.\n");
foreach ($categories as $category) {
    foreach ($category['items'] as $item) {
        $source = $root.'/public/uploads/menu-items/'.basename($item['image']);
        $zip->addFile($source, $item['image']);
    }
}
$zip->close();

echo "Created {$zipPath}\n";
echo 'Categories: '.count($categories).' | Items: '.array_sum(array_map(static fn (array $category): int => count($category['items']), $categories))."\n";
