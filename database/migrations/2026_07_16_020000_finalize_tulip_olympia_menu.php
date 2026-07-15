<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $restaurant = DB::table('restaurants')->where('slug', 'tulip-olympia')->first();
        if (! $restaurant) return;

        $catalog = [
            'Hot Beverage' => [
                'Coffee Americano' => 140, 'Café Latte' => 180, 'Coffee' => 140, 'Cappuccino' => 160,
                'Double Espresso' => 160, 'Double Macchiato' => 180, 'Espresso' => 140, 'Hot Chocolate' => 180,
                'Macchiato' => 150, 'Milk' => 160, 'Selection of Tea' => 100, 'Tea with Coffee' => 130,
                'Tea with Milk' => 150, 'Special Tea' => 230, 'Tea with Honey' => 150, 'Traditional Coffee' => 130,
            ],
            'Beer' => ['St. George' => 190, 'Habesha' => 190, 'Castel' => 190, 'Dashan' => 190, 'Kagan' => 190, 'Heineken' => 220, 'Arad' => 190],
            'Soft Drinks' => ['Soft Drinks' => 110, 'Small Water (600 ml)' => 110, 'Medium Water (1000 ml)' => 140, 'Big Water (2000 ml)' => 180],
            'Wines' => [
                'Roberson' => 4900, 'Local Glass of Wines' => 0, 'Acacia Dry Red' => 880, 'Acacia M.S. Rose' => 880,
                'Acacia M.S. Red' => 880, 'Acacia M.S. White' => 880, 'Acacia Medium Sweet White' => 3000,
                'Acacia Medium Sweet Red' => 3000, 'Acacia Medium Sweet Rose' => 3000, 'Acacia Dry Red (Bottle)' => 3000,
                'Rift Valley Merlot' => 3000, 'Rift Valley Chardonnay' => 3000, 'Rift Valley Syrah' => 3000,
                'Rift Valley Dry Rosé' => 3000, 'Rift Valley Cabernet Sauvignon' => 3000,
            ],
            'Aperitif' => ['Campari Bitter' => 310, 'Fernet Bianca' => 330, 'Martini Bianco' => 310, 'Martini Extra Dry' => 310, 'Martini Rosso' => 310, 'Pastis 51' => 320],
            'Gin' => ['Bombay Sapphire' => 500, 'Gordon’s' => 600, 'Tanqueray' => 380],
            'Vodka' => ['Absolut Blue' => 470, 'Cîroc' => 455, 'Grey Goose' => 470, 'Smirnoff Red (750 ml)' => 360, 'Stolichnaya Red (750 ml)' => 360, 'Stolichnaya Red (50 cl)' => 360, 'Winter Palace (1000 ml)' => 320],
            'Liquor' => ['Cointreau' => 400, 'Kahlúa' => 420, 'Sambuca (700 ml)' => 320, 'Tia Maria' => 320],
            'Rum' => ['Bacardi' => 270, 'Bacardi White' => 270, 'Captain Morgan Black' => 580, 'Captain Morgan Gold' => 580, 'Havana Club (1000 ml)' => 360, 'Malibu' => 400],
            'Regular Blended Whisky' => ['Johnnie Walker Double Black' => 1000, 'Johnnie Walker Black Label' => 880, 'Jack Daniel’s' => 880, 'J&B Rare' => 480],
            'Deluxe Whisky' => ['Chivas Regal 12 Years' => 535, 'Chivas Regal 18 Years' => 0, 'Johnnie Walker Gold' => 0, 'Johnnie Walker Platinum' => 0, 'Johnnie Walker Blue' => 0],
            'Single Malt Whisky' => ['Glenfiddich 12 Years' => 650, 'Glenfiddich 18 Years' => 0],
            'Tequila' => ['Jose Cuervo Silver' => 371, 'Tequila Camino Silver' => 496, 'Tequila Camino Gold' => 496],
            'Cognac & Brandy' => ['Courvoisier VS' => 0, 'Courvoisier VSOP' => 0],
            'Pizza Corner' => ['Four Season Pizza' => 0, 'Vegetarian Pizza' => 0, 'Margarita Pizza' => 0, 'Tuna Pizza' => 0],
            'Fresh Healthy Organic Fruit Juice' => ['Orange Juice' => 650, 'Papaya' => 700, 'Watermelon' => 350, 'Mixed Juice' => 330, 'Avocado' => 330],
            'Cake Corner' => ['Carrot Cake' => 330, 'English Cake' => 200, 'Banana Cake' => 200, 'Soft Cake' => 200, 'Cookies (1 kg)' => 680, 'Torta Cake' => 2000],
        ];

        $sort = 0;
        foreach ($catalog as $categoryName => $items) {
            $category = DB::table('categories')->where('restaurant_id', $restaurant->id)->where('name', $categoryName)->first();
            $categoryId = $category?->id ?: DB::table('categories')->insertGetId([
                'restaurant_id' => $restaurant->id, 'name' => $categoryName, 'sort_order' => ++$sort,
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);

            foreach ($items as $name => $price) {
                $existing = DB::table('menu_items')->where('restaurant_id', $restaurant->id)->where('name', $name)->first();
                $image = $existing?->image_path ?: 'uploads/menu-items/tulip-beverages.png';
                DB::table('menu_items')->updateOrInsert(
                    ['restaurant_id' => $restaurant->id, 'name' => $name],
                    [
                        'category_id' => $categoryId, 'price' => $price, 'image_path' => $image,
                        'description' => $price > 0 ? null : 'Price to be confirmed by Tulip Olympia.',
                        'is_available' => $price > 0, 'is_featured' => false, 'sort_order' => ++$sort,
                        'created_at' => $existing?->created_at ?: now(), 'updated_at' => now(),
                    ]
                );
            }
        }
    }
};
