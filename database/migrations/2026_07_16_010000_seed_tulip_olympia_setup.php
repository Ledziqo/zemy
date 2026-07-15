<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $restaurant = DB::table('restaurants')->where('slug', 'tulip-olympia')->first();

        if (! $restaurant) {
            return;
        }

        DB::table('restaurants')->where('id', $restaurant->id)->update([
            'name' => 'Tulip Olympia',
            'business_type' => 'hotel',
            'logo_path' => 'uploads/restaurants/tulip-olympia-logo.png',
            'updated_at' => now(),
        ]);

        $tableRows = [];
        for ($floor = 2; $floor <= 8; $floor++) {
            for ($room = 1; $room <= 15; $room++) {
                $number = (string) (($floor * 100) + $room);
                $tableRows[] = [$number, 'Room '.$number];
            }
        }
        for ($suite = 1; $suite <= 4; $suite++) {
            $number = '90'.$suite;
            $tableRows[] = [$number, 'Suite '.$number];
        }
        for ($table = 1; $table <= 21; $table++) {
            $tableRows[] = ['restaurant-'.$table, 'Restaurant Table '.$table];
        }
        for ($table = 1; $table <= 9; $table++) {
            $tableRows[] = ['lobby-'.$table, 'Lobby Table '.$table];
        }

        foreach ($tableRows as [$number, $name]) {
            DB::table('restaurant_tables')->updateOrInsert(
                ['restaurant_id' => $restaurant->id, 'table_number' => $number],
                ['table_name' => $name, 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $categories = [
            'Hot Beverages' => 10,
            'Food' => 20,
            'Fresh Juice' => 30,
            'Soft Drinks & Water' => 40,
            'Beer' => 50,
            'Wine' => 60,
            'Spirits' => 70,
        ];
        $categoryIds = [];
        foreach ($categories as $name => $sort) {
            $existing = DB::table('categories')->where('restaurant_id', $restaurant->id)->where('name', $name)->first();
            if ($existing) {
                $categoryIds[$name] = $existing->id;
                continue;
            }
            $categoryIds[$name] = DB::table('categories')->insertGetId([
                'restaurant_id' => $restaurant->id,
                'name' => $name,
                'sort_order' => $sort,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $foodPhoto = 'uploads/menu-items/tulip-food-selection.png';
        $drinkPhoto = 'uploads/menu-items/tulip-beverages.png';
        $items = [
            ['Hot Beverages', 'Coffee Americano', 140, $drinkPhoto],
            ['Hot Beverages', 'Cafe Late', 180, $drinkPhoto],
            ['Hot Beverages', 'Coffee', 140, $drinkPhoto],
            ['Hot Beverages', 'Cappuccino', 160, $drinkPhoto],
            ['Hot Beverages', 'Double espresso', 160, $drinkPhoto],
            ['Hot Beverages', 'Double macchiato', 180, $drinkPhoto],
            ['Hot Beverages', 'Espresso', 140, $drinkPhoto],
            ['Hot Beverages', 'Hot chocolate', 180, $drinkPhoto],
            ['Hot Beverages', 'Macchiato', 150, $drinkPhoto],
            ['Hot Beverages', 'Milk', 160, $drinkPhoto],
            ['Hot Beverages', 'Selection of tea', 100, $drinkPhoto],
            ['Hot Beverages', 'Tea with coffee', 130, $drinkPhoto],
            ['Hot Beverages', 'Tea with milk', 150, $drinkPhoto],
            ['Hot Beverages', 'Special Tea', 230, $drinkPhoto],
            ['Hot Beverages', 'Tea with Honey', 150, $drinkPhoto],
            ['Hot Beverages', 'Traditional Coffee', 130, $drinkPhoto],
            ['Food', 'Pasta', 0, $foodPhoto],
            ['Food', 'Pizza', 0, $foodPhoto],
            ['Food', 'Burger', 0, $foodPhoto],
            ['Food', 'Grilled Steak', 0, $foodPhoto],
            ['Food', 'Fresh Salad', 0, $foodPhoto],
            ['Fresh Juice', 'Orange Juice', 650, $drinkPhoto],
            ['Fresh Juice', 'Papaya Juice', 700, $drinkPhoto],
            ['Fresh Juice', 'Watermelon Juice', 350, $drinkPhoto],
            ['Fresh Juice', 'Mixed Juice', 330, $drinkPhoto],
            ['Fresh Juice', 'Avocado Juice', 330, $drinkPhoto],
            ['Soft Drinks & Water', 'Soft drinks', 110, $drinkPhoto],
            ['Soft Drinks & Water', 'Small Water 600ml', 110, $drinkPhoto],
            ['Soft Drinks & Water', 'Medium Water 1000ml', 140, $drinkPhoto],
            ['Soft Drinks & Water', 'Big Water 2000ml', 180, $drinkPhoto],
            ['Beer', 'St. George', 190, $drinkPhoto],
            ['Beer', 'Habesha', 190, $drinkPhoto],
            ['Beer', 'Castel', 190, $drinkPhoto],
            ['Beer', 'Dashan', 190, $drinkPhoto],
            ['Beer', 'Kagan', 190, $drinkPhoto],
            ['Beer', 'Heineken', 220, $drinkPhoto],
            ['Beer', 'Arad', 190, $drinkPhoto],
            ['Wine', 'Robertson Bottle', 4900, $drinkPhoto],
            ['Wine', 'Acacia Dry Red Glass', 880, $drinkPhoto],
            ['Wine', 'Acacia M.S. Rose Glass', 880, $drinkPhoto],
            ['Wine', 'Acacia M.S. Red Glass', 880, $drinkPhoto],
            ['Wine', 'Acacia M.S. White Glass', 880, $drinkPhoto],
            ['Spirits', 'Campari Bitter Glass', 310, $drinkPhoto],
            ['Spirits', 'Fernet Bianca Glass', 330, $drinkPhoto],
            ['Spirits', 'Martini Bianco Glass', 310, $drinkPhoto],
            ['Spirits', 'Martini Extra Dry Glass', 310, $drinkPhoto],
            ['Spirits', 'Bombay Sapphire Glass', 500, $drinkPhoto],
            ['Spirits', 'Gordon’s Glass', 600, $drinkPhoto],
            ['Spirits', 'Tanqueray Glass', 380, $drinkPhoto],
        ];

        foreach ($items as $index => [$category, $name, $price, $image]) {
            DB::table('menu_items')->updateOrInsert(
                ['restaurant_id' => $restaurant->id, 'name' => $name],
                [
                    'category_id' => $categoryIds[$category],
                    'description' => $price === 0 ? 'Price to be confirmed by Tulip Olympia.' : null,
                    'price' => $price,
                    'image_path' => $image,
                    'is_available' => $price > 0,
                    'is_featured' => in_array($name, ['Pasta', 'Pizza', 'Burger', 'Grilled Steak'], true),
                    'sort_order' => ($index + 1) * 10,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        $restaurant = DB::table('restaurants')->where('slug', 'tulip-olympia')->first();
        if (! $restaurant) {
            return;
        }

        DB::table('restaurant_tables')->where('restaurant_id', $restaurant->id)->where(function ($query) {
            $query->whereBetween('table_number', ['201', '815'])
                ->orWhere('table_number', 'like', '90%')
                ->orWhere('table_number', 'like', 'restaurant-%')
                ->orWhere('table_number', 'like', 'lobby-%');
        })->delete();
        DB::table('menu_items')->where('restaurant_id', $restaurant->id)->delete();
        DB::table('categories')->where('restaurant_id', $restaurant->id)->whereIn('name', array_keys([
            'Hot Beverages' => true, 'Food' => true, 'Fresh Juice' => true, 'Soft Drinks & Water' => true,
            'Beer' => true, 'Wine' => true, 'Spirits' => true,
        ]))->delete();
    }
};
