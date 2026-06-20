<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'Aesliexx@gmail.com'],
            ['name' => 'ZemTab Admin', 'password' => Hash::make('Mudi2005'), 'role' => 'admin']
        );

        $restaurant = Restaurant::updateOrCreate(
            ['slug' => 'bole-bistro'],
            [
                'name' => 'Bole Bistro Demo',
                'business_type' => 'restaurant',
                'phone' => '+251 911 000 000',
                'email' => 'hello@bolebistro.test',
                'location' => 'Bole, Addis Ababa',
                'primary_color' => '#D89B35',
                'is_active' => true,
                'dashboard_access_status' => 'active',
                'settings' => [
                    'service_charge_percentage' => 0,
                    'vat_percentage' => 0,
                    'payment_methods' => ['cash', 'telebirr', 'cbe'],
                ],
            ]
        );

        User::updateOrCreate(
            ['email' => 'owner@bolebistro.test'],
            ['name' => 'Bole Bistro Owner', 'password' => Hash::make('password'), 'role' => 'restaurant_owner', 'restaurant_id' => $restaurant->id]
        );

        User::updateOrCreate(
            ['email' => 'staff@bolebistro.test'],
            ['name' => 'Bole Bistro Staff', 'password' => Hash::make('password'), 'role' => 'staff', 'restaurant_id' => $restaurant->id]
        );

        $items = [
            'Breakfast' => [
                ['Egg Sandwich', 180, 'Toasted bread with eggs, tomato, and house sauce.'],
                ['Ful Special', 220, 'Warm ful with fresh herbs, onion, tomato, and chili.'],
                ['Pancake with Honey', 250, 'Soft pancakes with Ethiopian honey.'],
            ],
            'Burgers' => [
                ['Special Beef Burger', 420, 'Beef patty, cheese, lettuce, tomato, and signature sauce.'],
                ['Chicken Burger', 390, 'Grilled chicken, crisp lettuce, and creamy sauce.'],
                ['Double Cheese Burger', 520, 'Two beef patties with melted cheese.'],
            ],
            'Pizza' => [
                ['Margherita Pizza', 550, 'Tomato, mozzarella, and basil.'],
                ['Chicken Pizza', 680, 'Chicken, peppers, onion, and mozzarella.'],
                ['Meat Lovers Pizza', 750, 'Loaded beef, chicken, sausage, and cheese.'],
            ],
            'Local Food' => [
                ['Beef Tibs', 550, 'Pan-seared beef with rosemary, jalapeno, and injera.'],
                ['Kitfo', 650, 'Seasoned minced beef with mitmita and ayib.'],
                ['Shiro Tegabino', 300, 'Rich chickpea stew served hot with injera.'],
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
            'Desserts' => [
                ['Chocolate Cake', 260, 'Moist chocolate cake slice.'],
                ['Tiramisu Cup', 320, 'Coffee cream dessert cup.'],
            ],
        ];

        $sort = 1;
        foreach ($items as $categoryName => $menuItems) {
            $category = Category::updateOrCreate(
                ['restaurant_id' => $restaurant->id, 'name' => $categoryName],
                ['sort_order' => $sort++, 'is_active' => true]
            );

            foreach ($menuItems as $index => [$name, $price, $description]) {
                MenuItem::updateOrCreate(
                    ['restaurant_id' => $restaurant->id, 'name' => $name],
                    [
                        'category_id' => $category->id,
                        'description' => $description,
                        'price' => $price,
                        'image_path' => null,
                        'is_available' => true,
                        'is_featured' => $index === 0,
                        'sort_order' => $index + 1,
                    ]
                );
            }
        }

        for ($i = 1; $i <= 10; $i++) {
            RestaurantTable::updateOrCreate(
                ['restaurant_id' => $restaurant->id, 'table_number' => (string) $i],
                ['table_name' => 'Table '.$i, 'is_active' => true]
            );
        }

        Subscription::updateOrCreate(
            ['restaurant_id' => $restaurant->id, 'plan_name' => 'Pro'],
            ['monthly_price' => 5000, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth()]
        );
    }
}
