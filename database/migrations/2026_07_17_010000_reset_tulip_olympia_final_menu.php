<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $restaurant = DB::table('restaurants')->where('slug', 'tulip-olympia')->first();
        if (! $restaurant) return;

        DB::transaction(function () use ($restaurant) {
            $restaurantId = $restaurant->id;
            DB::table('menu_items')->where('restaurant_id', $restaurantId)->delete();
            DB::table('categories')->where('restaurant_id', $restaurantId)->delete();

            $catalog = [
                'Hot Beverage (Cup)' => [
                    'Coffee Americano' => [140, 'cup'], 'Café Latte' => [180, 'cup'], 'Coffee' => [140, 'cup'], 'Cappuccino' => [160, 'cup'],
                    'Double Espresso' => [160, 'cup'], 'Double Macchiato' => [180, 'cup'], 'Espresso' => [140, 'cup'], 'Hot Chocolate' => [180, 'cup'],
                    'Macchiato' => [150, 'cup'], 'Milk' => [160, 'cup'], 'Selection of Tea' => [100, 'cup'], 'Tea with Coffee' => [130, 'cup'],
                    'Tea with Milk' => [150, 'cup'], 'Special Tea' => [230, 'cup'], 'Tea with Honey' => [150, 'cup'], 'Traditional Coffee' => [130, 'cup'],
                ],
                'Beer (Bottle)' => ['St. George' => [190, 'bottle'], 'Habesha' => [190, 'bottle'], 'Castel' => [190, 'bottle'], 'Dashan' => [190, 'bottle'], 'Kagan' => [190, 'bottle'], 'Heineken' => [220, 'bottle'], 'Arad' => [190, 'bottle']],
                'Soft Drinks (Bottle)' => ['Soft Drinks' => [110, 'bottle'], 'Small Water (600 ml)' => [110, 'bottle'], 'Medium Water (1000 ml)' => [140, 'bottle'], 'Big Water (2000 ml)' => [180, 'bottle']],
                'Wines' => [
                    'Roberson (Bottle)' => [4900, 'bottle'], 'Local Glass of Wine' => [880, 'glass'], 'Acacia Dry Red (Glass)' => [880, 'glass'], 'Acacia M.S. Rose (Glass)' => [880, 'glass'], 'Acacia M.S. Red (Glass)' => [880, 'glass'], 'Acacia M.S. White (Glass)' => [880, 'glass'],
                    'Acacia Medium Sweet White (Bottle)' => [3000, 'bottle'], 'Acacia Medium Sweet Red (Bottle)' => [3000, 'bottle'], 'Acacia Medium Sweet Rose (Bottle)' => [3000, 'bottle'], 'Acacia Dry Red (Bottle)' => [3000, 'bottle'], 'Rift Valley Merlot (Bottle)' => [3000, 'bottle'], 'Rift Valley Chardonnay (Bottle)' => [3000, 'bottle'], 'Rift Valley Syrah (Bottle)' => [3000, 'bottle'], 'Rift Valley Dry Rosé (Bottle)' => [3000, 'bottle'], 'Rift Valley Cabernet Sauvignon (Bottle)' => [3000, 'bottle'],
                ],
                'Aperitif' => ['Campari Bitter' => [310, 6965], 'Fernet Bianca' => [330, 7085], 'Martini Bianco' => [310, 6965], 'Martini Extra Dry' => [310, null], 'Martini Rosso' => [310, 6965], 'Pastis 51' => [320, 7915]],
                'Gin' => ['Bombay Sapphire' => [500, 16270], 'Gordon’s' => [600, 17900], 'Tanqueray' => [380, 6160]],
                'Vodka' => ['Absolut Blue' => [470, 13630], 'Cîroc' => [455, 9730], 'Grey Goose' => [470, 10630], 'Smirnoff Red (750 ml)' => [360, 9100], 'Stolichnaya Red (750 ml)' => [360, 9100], 'Stolichnaya Red (50 cl)' => [360, 4050], 'Winter Palace (1000 ml)' => [320, 10400]],
                'Liquor' => ['Cointreau' => [400, 8930], 'Kahlúa' => [420, 5240], 'Sambuca (700 ml)' => [320, 6390], 'Tia Maria' => [320, 5050]],
                'Rum' => ['Bacardi' => [270, 5380], 'Bacardi White' => [270, 5380], 'Captain Morgan Black' => [580, 8650], 'Captain Morgan Gold' => [580, 8650], 'Havana Club (1000 ml)' => [360, 5500], 'Malibu' => [400, 11000]],
                'Regular Blended Whisky' => ['Johnnie Walker Double Black' => [1000, 35500], 'Johnnie Walker Black Label' => [880, 30000], 'Jack Daniel’s' => [880, 30000], 'J&B Rare' => [480, null]],
                'Deluxe Whisky' => ['Chivas Regal 12 Years' => [535, 20930], 'Chivas Regal 18 Years' => [null, 38000], 'Johnnie Walker Gold' => [null, 44155], 'Johnnie Walker Platinum' => [null, 34755], 'Johnnie Walker Blue' => [null, 68310]],
                'Single Malt Whisky' => ['Glenfiddich 12 Years' => [650, 25560], 'Glenfiddich 18 Years' => [null, 27800]],
                'Tequila' => ['Jose Cuervo Silver' => [371, 9100], 'Tequila Camino Silver' => [496, 10695], 'Tequila Camino Gold' => [496, 10695]],
                'Cognac & Brandy' => ['Courvoisier VS' => [null, 30000], 'Courvoisier VSOP' => [null, 30000]],
                'Pizza Corner' => ['Four Season Pizza' => [750, 'item'], 'Vegetarian Pizza' => [700, 'item'], 'Margarita Pizza' => [750, 'item'], 'Tuna Pizza' => [700, 'item']],
                'Fresh Healthy Organic Fruit Juice' => ['Orange Juice' => [350, 'item'], 'Papaya' => [350, 'item'], 'Watermelon' => [330, 'item'], 'Mixed Juice' => [330, 'item'], 'Avocado' => [350, 'item']],
                'Cake Corner' => ['Carrot Cake' => [330, 'item'], 'English Cake' => [200, 'item'], 'Banana Cake' => [200, 'item'], 'Soft Cake' => [200, 'item'], 'Cookies (1 kg)' => [680, 'item'], 'Torta Cake' => [2000, 'item']],
            ];

            $photoByCategory = [
                'Hot Beverage (Cup)' => 'uploads/menu-items/tulip-beverages.png', 'Beer (Bottle)' => 'uploads/menu-items/tulip-beer.png', 'Soft Drinks (Bottle)' => 'uploads/menu-items/tulip-soft-drink.png', 'Wines' => 'uploads/menu-items/tulip-red-wine.png',
                'Aperitif' => 'uploads/menu-items/tulip-red-wine.png', 'Gin' => 'uploads/menu-items/tulip-gin.png', 'Vodka' => 'uploads/menu-items/tulip-gin.png', 'Liquor' => 'uploads/menu-items/tulip-whisky.png', 'Rum' => 'uploads/menu-items/tulip-whisky.png', 'Regular Blended Whisky' => 'uploads/menu-items/tulip-whisky.png', 'Deluxe Whisky' => 'uploads/menu-items/tulip-whisky.png', 'Single Malt Whisky' => 'uploads/menu-items/tulip-whisky.png', 'Tequila' => 'uploads/menu-items/tulip-gin.png', 'Cognac & Brandy' => 'uploads/menu-items/tulip-whisky.png',
                'Pizza Corner' => 'uploads/menu-items/tulip-pizza.png', 'Fresh Healthy Organic Fruit Juice' => 'uploads/menu-items/tulip-orange-juice.png', 'Cake Corner' => 'uploads/menu-items/tulip-cake.png',
            ];

            $sort = 0;
            foreach ($catalog as $categoryName => $items) {
                $categoryId = DB::table('categories')->insertGetId(['restaurant_id' => $restaurantId, 'name' => $categoryName, 'sort_order' => ++$sort, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
                $itemSort = 0;
                foreach ($items as $name => $values) {
                    [$glass, $bottleOrUnit] = $values;
                    $isVariantPair = is_array($values) && count($values) === 2 && is_int($bottleOrUnit);
                    $rows = $isVariantPair ? array_filter([[$name.' (Glass)', $glass, 'Glass'], [$name.' (Bottle)', $bottleOrUnit, 'Bottle']], fn ($row) => $row[1] !== null) : [[$name, $glass, ucfirst((string) $bottleOrUnit)]];
                    foreach ($rows as [$itemName, $price, $unit]) {
                        $source = 'https://www.google.com/search?tbm=isch&q='.urlencode($itemName.' '.$categoryName);
                        DB::table('menu_items')->insert(['restaurant_id' => $restaurantId, 'category_id' => $categoryId, 'name' => $itemName, 'description' => $unit.' serving. Prices include 10% service charge and 15% VAT.', 'price' => $price, 'image_path' => $photoByCategory[$categoryName], 'image_source_url' => $source, 'is_available' => true, 'is_featured' => false, 'sort_order' => ++$itemSort, 'created_at' => now(), 'updated_at' => now()]);
                    }
                }
            }

            $settings = json_decode($restaurant->settings ?: '{}', true) ?: [];
            $settings['service_charge_percentage'] = 0;
            $settings['vat_percentage'] = 0;
            DB::table('restaurants')->where('id', $restaurantId)->update(['settings' => json_encode($settings), 'menu_cache_version' => ((int) ($restaurant->menu_cache_version ?? 1)) + 1, 'updated_at' => now()]);
        });
    }
};
