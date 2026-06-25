<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('restaurants')->updateOrInsert(
            ['slug' => 'ginashotel'],
            [
                'name' => "Gina's Hotel",
                'business_type' => 'hotel',
                'phone' => '+251 974 217 074',
                'email' => 'ginashotel@zemtab.com',
                'location' => 'Addis Ababa, Ethiopia',
                'primary_color' => '#D22630',
                'is_active' => true,
                'dashboard_access_status' => 'active',
                'settings' => json_encode([
                    'service_charge_percentage' => 0,
                    'vat_percentage' => 0,
                    'payment_methods' => ['cash', 'telebirr', 'cbe'],
                ]),
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $hotelId = (int) DB::table('restaurants')->where('slug', 'ginashotel')->value('id');

        DB::table('users')->updateOrInsert(
            ['email' => 'owner@ginashotel.test'],
            [
                'name' => "Gina's Hotel Owner",
                'password' => Hash::make('password'),
                'role' => 'restaurant_owner',
                'restaurant_id' => $hotelId,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('users')->updateOrInsert(
            ['email' => 'staff@ginashotel.test'],
            [
                'name' => "Gina's Hotel Staff",
                'password' => Hash::make('password'),
                'role' => 'staff',
                'restaurant_id' => $hotelId,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $menu = [
            'Breakfast' => [
                ['English Breakfast', 480, 'Eggs, toast, sausage, tomato, and tea or coffee.'],
                ['Pancakes with Honey', 260, 'Soft pancakes served with Ethiopian honey.'],
                ['Ful Special', 220, 'Warm ful with fresh herbs, onion, tomato, and chili.'],
            ],
            'Bakery' => [
                ['Chocolate Croissant', 180, 'Fresh baked croissant with chocolate filling.'],
                ['Cinnamon Roll', 170, 'Soft roll with cinnamon sugar glaze.'],
                ['Banana Bread Slice', 140, 'Moist banana bread baked in-house.'],
            ],
            'Cakes' => [
                ['Chocolate Cake', 260, 'Moist chocolate cake slice.'],
                ['Red Velvet Cake', 290, 'Classic red velvet with cream frosting.'],
                ['Cheesecake Cup', 320, 'Creamy cheesecake served in a cup.'],
            ],
            'Sandwiches' => [
                ['Club Sandwich', 420, 'Chicken, egg, lettuce, tomato, and fries.'],
                ['Tuna Sandwich', 340, 'Tuna, herbs, and house sauce on toasted bread.'],
                ['Grilled Cheese', 280, 'Melted cheese sandwich with tomato dip.'],
            ],
            'Room Service Meals' => [
                ['Chicken Pasta', 520, 'Creamy pasta with grilled chicken.'],
                ['Beef Tibs', 550, 'Pan-seared beef with rosemary, jalapeno, and injera.'],
                ['Margherita Pizza', 550, 'Tomato, mozzarella, and basil.'],
            ],
            'Hot Drinks' => [
                ['Macchiato', 90, 'Classic Ethiopian macchiato.'],
                ['Ethiopian Coffee', 80, 'Fresh brewed buna.'],
                ['Tea', 60, 'Hot spiced tea.'],
            ],
            'Juices' => [
                ['Mango Juice', 180, 'Fresh mango juice.'],
                ['Avocado Juice', 180, 'Creamy avocado juice.'],
                ['Mixed Juice', 220, 'Layered seasonal fruit juice.'],
            ],
        ];

        $categorySort = 1;
        foreach ($menu as $categoryName => $items) {
            DB::table('categories')->updateOrInsert(
                ['restaurant_id' => $hotelId, 'name' => $categoryName],
                [
                    'sort_order' => $categorySort++,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $categoryId = (int) DB::table('categories')
                ->where('restaurant_id', $hotelId)
                ->where('name', $categoryName)
                ->value('id');

            foreach ($items as $index => [$name, $price, $description]) {
                DB::table('menu_items')->updateOrInsert(
                    ['restaurant_id' => $hotelId, 'name' => $name],
                    [
                        'category_id' => $categoryId,
                        'description' => $description,
                        'price' => $price,
                        'image_path' => null,
                        'is_available' => true,
                        'is_featured' => $index === 0,
                        'sort_order' => $index + 1,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }

        foreach (['101', '102', '103', '104', '105', '201', '202', '203', '204', '205'] as $roomNumber) {
            DB::table('restaurant_tables')->updateOrInsert(
                ['restaurant_id' => $hotelId, 'table_number' => $roomNumber],
                [
                    'table_name' => 'Room '.$roomNumber,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        DB::table('subscriptions')->updateOrInsert(
            ['restaurant_id' => $hotelId, 'plan_name' => 'Pro'],
            [
                'monthly_price' => null,
                'status' => 'active',
                'starts_at' => $now->toDateString(),
                'ends_at' => $now->copy()->addMonth()->toDateString(),
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        $hotelId = DB::table('restaurants')->where('slug', 'ginashotel')->value('id');

        if (! $hotelId) {
            return;
        }

        DB::table('users')->whereIn('email', ['owner@ginashotel.test', 'staff@ginashotel.test'])->delete();
        DB::table('subscriptions')->where('restaurant_id', $hotelId)->delete();
        DB::table('restaurant_tables')->where('restaurant_id', $hotelId)->delete();
        DB::table('menu_items')->where('restaurant_id', $hotelId)->delete();
        DB::table('categories')->where('restaurant_id', $hotelId)->delete();
        DB::table('restaurants')->where('id', $hotelId)->delete();
    }
};
