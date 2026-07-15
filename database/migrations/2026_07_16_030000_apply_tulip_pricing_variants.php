<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $restaurant = DB::table('restaurants')->where('slug', 'tulip-olympia')->first();
        if (! $restaurant) return;

        $variants = [
            'Aperitif' => [
                'Campari Bitter' => [310, 6965], 'Fernet Bianca' => [330, 7085], 'Martini Bianco' => [310, 6965],
                'Martini Extra Dry' => [310, null], 'Martini Rosso' => [310, 6965], 'Pastis 51' => [320, 7915],
            ],
            'Gin' => ['Bombay Sapphire' => [500, 16270], 'Gordon’s' => [600, 17900], 'Tanqueray' => [380, 6160]],
            'Vodka' => [
                'Absolut Blue' => [470, 13630], 'Cîroc' => [455, 9730], 'Grey Goose' => [470, 10630],
                'Smirnoff Red (750 ml)' => [360, 9100], 'Stolichnaya Red (750 ml)' => [360, 9100],
                'Stolichnaya Red (50 cl)' => [360, 4050], 'Winter Palace (1000 ml)' => [320, 10400],
            ],
            'Liquor' => ['Cointreau' => [400, 8930], 'Kahlúa' => [420, 5240], 'Sambuca (700 ml)' => [320, 6390], 'Tia Maria' => [320, 5050]],
            'Rum' => [
                'Bacardi' => [270, 5380], 'Bacardi White' => [270, 5380], 'Captain Morgan Black' => [580, 8650],
                'Captain Morgan Gold' => [580, 8650], 'Havana Club (1000 ml)' => [360, 5500], 'Malibu' => [400, 11000],
            ],
            'Regular Blended Whisky' => [
                'Johnnie Walker Double Black' => [1000, 35500], 'Johnnie Walker Black Label' => [880, 30000],
                'Jack Daniel’s' => [880, 30000], 'J&B Rare' => [480, null],
            ],
            'Deluxe Whisky' => [
                'Chivas Regal 12 Years' => [535, 20930], 'Chivas Regal 18 Years' => [null, 38000],
                'Johnnie Walker Gold' => [null, 44155], 'Johnnie Walker Platinum' => [null, 34755], 'Johnnie Walker Blue' => [null, 68310],
            ],
            'Single Malt Whisky' => ['Glenfiddich 12 Years' => [650, 25560], 'Glenfiddich 18 Years' => [null, 27800]],
            'Tequila' => ['Jose Cuervo Silver' => [371, 9100], 'Tequila Camino Silver' => [496, 10695], 'Tequila Camino Gold' => [496, 10695]],
            'Cognac & Brandy' => ['Courvoisier VS' => [null, 30000], 'Courvoisier VSOP' => [null, 30000]],
        ];

        foreach ($variants as $categoryName => $items) {
            $category = DB::table('categories')->where('restaurant_id', $restaurant->id)->where('name', $categoryName)->first();
            if (! $category) continue;
            foreach ($items as $baseName => [$glass, $bottle]) {
                $current = DB::table('menu_items')->where('restaurant_id', $restaurant->id)->where('name', $baseName)->first();
                if ($glass !== null) {
                    $glassName = $bottle !== null ? $baseName.' (Glass)' : $baseName;
                    if ($current) {
                        DB::table('menu_items')->where('id', $current->id)->update(['name' => $glassName, 'price' => $glass, 'is_available' => true, 'description' => 'Glass serving.', 'updated_at' => now()]);
                    } else {
                        DB::table('menu_items')->updateOrInsert(['restaurant_id' => $restaurant->id, 'name' => $glassName], ['category_id' => $category->id, 'price' => $glass, 'image_path' => 'uploads/menu-items/tulip-beverages.png', 'description' => 'Glass serving.', 'is_available' => true, 'is_featured' => false, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
                    }
                } elseif ($current) {
                    DB::table('menu_items')->where('id', $current->id)->update(['name' => $baseName.' (Bottle)', 'price' => $bottle, 'is_available' => true, 'description' => 'Bottle serving.', 'updated_at' => now()]);
                }
                if ($bottle !== null && $glass !== null) {
                    DB::table('menu_items')->updateOrInsert(['restaurant_id' => $restaurant->id, 'name' => $baseName.' (Bottle)'], ['category_id' => $category->id, 'price' => $bottle, 'image_path' => 'uploads/menu-items/tulip-beverages.png', 'description' => 'Bottle serving.', 'is_available' => true, 'is_featured' => false, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }

        $prices = [
            'Four Season Pizza' => 750, 'Vegetarian Pizza' => 700, 'Margarita Pizza' => 750, 'Tuna Pizza' => 700,
            'Orange Juice' => 350, 'Papaya' => 350, 'Watermelon' => 330, 'Mixed Juice' => 330, 'Avocado' => 350,
            'Carrot Cake' => 330, 'English Cake' => 200, 'Banana Cake' => 200, 'Soft Cake' => 200, 'Cookies (1 kg)' => 680, 'Torta Cake' => 2000,
        ];
        DB::table('menu_items')->where('restaurant_id', $restaurant->id)->whereIn('name', array_keys($prices))->update(['is_available' => true, 'updated_at' => now()]);
        foreach ($prices as $name => $price) DB::table('menu_items')->where('restaurant_id', $restaurant->id)->where('name', $name)->update(['price' => $price, 'description' => null, 'updated_at' => now()]);

        DB::table('menu_items')->where('restaurant_id', $restaurant->id)->whereIn('name', ['Pasta', 'Pizza', 'Burger', 'Grilled Steak', 'Fresh Salad'])->delete();
        DB::table('categories')->where('restaurant_id', $restaurant->id)->whereIn('name', ['Food', 'Fresh Juice'])->delete();

        $settings = json_decode($restaurant->settings ?: '{}', true) ?: [];
        $settings['service_charge_percentage'] = 0;
        $settings['vat_percentage'] = 0;
        DB::table('restaurants')->where('id', $restaurant->id)->update(['settings' => json_encode($settings), 'updated_at' => now()]);
    }
};
