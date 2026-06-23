<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StressTestSeeder extends Seeder
{
    public function run(): void
    {
        $count = (int) env('STRESS_SEED_COUNT', 300);

        $itemTemplates = [
            'Mains' => [
                ['Beef Tibs', 550], ['Kitfo', 650], ['Shiro Tegabino', 300],
                ['Doro Wat', 480], ['Tibs Special', 520], ['Firfir', 280],
            ],
            'Drinks' => [
                ['Macchiato', 90], ['Coffee', 80], ['Tea', 60],
                ['Mango Juice', 180], ['Avocado Juice', 180], ['Mixed Juice', 220],
            ],
            'Snacks' => [
                ['Egg Sandwich', 180], ['Ful Special', 220], ['Pancake', 250],
                ['Chocolate Cake', 260], ['Tiramisu', 320], ['Fruit Salad', 200],
            ],
            'Burgers' => [
                ['Beef Burger', 420], ['Chicken Burger', 390], ['Cheese Burger', 520],
            ],
            'Pizza' => [
                ['Margherita', 550], ['Chicken Pizza', 680], ['Meat Lovers', 750],
            ],
        ];

        for ($i = 1; $i <= $count; $i++) {
            $slug = 'zt-stress-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $email = $slug . '@zemtab.test';

            $restaurant = Restaurant::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => 'Stress Test Restaurant ' . $i,
                    'business_type' => 'restaurant',
                    'phone' => '+251 911 ' . str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                    'email' => $email,
                    'location' => 'Test Location ' . $i,
                    'primary_color' => '#D22630',
                    'is_active' => true,
                    'dashboard_access_status' => 'active',
                    'settings' => [
                        'service_charge_percentage' => 0,
                        'vat_percentage' => 0,
                        'payment_methods' => ['cash'],
                    ],
                ]
            );

            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => 'Stress Restaurant ' . $i,
                    'password' => Hash::make('password'),
                    'role' => 'restaurant_owner',
                    'restaurant_id' => $restaurant->id,
                ]
            );

            $sort = 1;
            foreach ($itemTemplates as $categoryName => $menuItems) {
                $category = Category::updateOrCreate(
                    ['restaurant_id' => $restaurant->id, 'name' => $categoryName],
                    ['sort_order' => $sort++, 'is_active' => true]
                );

                foreach ($menuItems as $index => [$name, $price]) {
                    MenuItem::updateOrCreate(
                        ['restaurant_id' => $restaurant->id, 'name' => $name],
                        [
                            'category_id' => $category->id,
                            'description' => 'Stress test item.',
                            'price' => $price,
                            'image_path' => null,
                            'is_available' => true,
                            'is_featured' => $index === 0,
                            'sort_order' => $index + 1,
                        ]
                    );
                }
            }

            for ($t = 1; $t <= 10; $t++) {
                RestaurantTable::updateOrCreate(
                    ['restaurant_id' => $restaurant->id, 'table_number' => (string) $t],
                    ['table_name' => 'Table ' . $t, 'is_active' => true]
                );
            }

            Subscription::updateOrCreate(
                ['restaurant_id' => $restaurant->id, 'plan_name' => 'Pro'],
                ['monthly_price' => 5000, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth()]
            );
        }

        $this->command->info("StressTestSeeder: created {$count} restaurants with accounts, menu items, and tables.");
    }

    public static function cleanup(): void
    {
        $count = (int) env('STRESS_SEED_COUNT', 300);
        $slugs = collect(range(1, $count))
            ->map(fn ($i) => 'zt-stress-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT));

        $restaurantIds = Restaurant::whereIn('slug', $slugs)->pluck('id');

        if ($restaurantIds->isEmpty()) {
            return;
        }

        // Get order IDs before deleting orders (for order_items cleanup)
        $orderIds = DB::table('orders')->whereIn('restaurant_id', $restaurantIds)->pluck('id');

        // Delete children first to respect foreign keys
        if ($orderIds->isNotEmpty()) {
            DB::table('order_items')->whereIn('order_id', $orderIds)->delete();
        }
        DB::table('orders')->whereIn('restaurant_id', $restaurantIds)->delete();
        DB::table('service_requests')->whereIn('restaurant_id', $restaurantIds)->delete();
        DB::table('menu_items')->whereIn('restaurant_id', $restaurantIds)->delete();
        DB::table('categories')->whereIn('restaurant_id', $restaurantIds)->delete();
        DB::table('restaurant_tables')->whereIn('restaurant_id', $restaurantIds)->delete();
        DB::table('subscriptions')->whereIn('restaurant_id', $restaurantIds)->delete();
        DB::table('guest_sessions')->whereIn('restaurant_id', $restaurantIds)->delete();
        DB::table('payments')->whereIn('restaurant_id', $restaurantIds)->delete();

        // Delete users associated with these restaurants
        User::whereIn('email', $slugs->map(fn ($s) => $s . '@zemtab.test'))->delete();

        // Delete the restaurants
        Restaurant::whereIn('id', $restaurantIds)->delete();
    }
}