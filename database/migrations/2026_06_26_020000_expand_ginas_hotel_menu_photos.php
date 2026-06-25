<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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

        $categories = [
            'Breakfast' => [
                ['English Breakfast', 480, 'Eggs, toast, sausage, tomato, and tea or coffee.', 'https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?auto=format&fit=crop&w=1200&q=80'],
                ['Pancakes with Honey', 260, 'Soft pancakes served with Ethiopian honey.', 'https://images.unsplash.com/photo-1528207776546-365bb710ee93?auto=format&fit=crop&w=1200&q=80'],
                ['Ful Special', 220, 'Warm ful with fresh herbs, onion, tomato, and chili.', null],
            ],
            'Bakery' => [
                ['Chocolate Croissant', 180, 'Fresh baked croissant with chocolate filling.', 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=1200&q=80'],
                ['Cinnamon Roll', 170, 'Soft roll with cinnamon sugar glaze.', 'https://images.unsplash.com/photo-1509365465985-25d11c17e812?auto=format&fit=crop&w=1200&q=80'],
                ['Banana Bread Slice', 140, 'Moist banana bread baked in-house.', null],
            ],
            'Pasta' => [
                ['Chicken Alfredo Pasta', 520, 'Creamy pasta with grilled chicken and parmesan.', 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?auto=format&fit=crop&w=1200&q=80'],
                ['Spaghetti Bolognese', 540, 'Slow-cooked beef tomato sauce over spaghetti.', 'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?auto=format&fit=crop&w=1200&q=80'],
                ['Vegetable Penne', 430, 'Penne pasta with seasonal vegetables and light tomato sauce.', null],
            ],
            'Pizza' => [
                ['Margherita Pizza', 550, 'Tomato, mozzarella, and basil.', 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=1200&q=80'],
                ['Chicken Pizza', 680, 'Chicken, peppers, onion, and mozzarella.', 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=1200&q=80'],
                ['Meat Lovers Pizza', 750, 'Loaded beef, chicken, sausage, and cheese.', null],
            ],
            'Burgers' => [
                ['Classic Beef Burger', 460, 'Beef patty, cheese, lettuce, tomato, and house sauce.', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=1200&q=80'],
                ['Chicken Burger', 420, 'Grilled chicken, crisp lettuce, and creamy sauce.', 'https://images.unsplash.com/photo-1606755962773-d324e2a13086?auto=format&fit=crop&w=1200&q=80'],
                ['Double Cheese Burger', 560, 'Two beef patties with melted cheese and fries.', null],
            ],
            'Steaks & Grill' => [
                ['Grilled Steak', 950, 'Juicy grilled steak with vegetables and fries.', 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?auto=format&fit=crop&w=1200&q=80'],
                ['Pepper Steak', 980, 'Tender beef steak with pepper sauce and mashed potatoes.', 'https://images.unsplash.com/photo-1558030006-450675393462?auto=format&fit=crop&w=1200&q=80'],
                ['Grilled Chicken Plate', 620, 'Seasoned grilled chicken with salad and fries.', null],
            ],
            'Local Food' => [
                ['Beef Tibs', 550, 'Pan-seared beef with rosemary, jalapeno, and injera.', null],
                ['Kitfo', 650, 'Seasoned minced beef with mitmita and ayib.', null],
                ['Shiro Tegabino', 300, 'Rich chickpea stew served hot with injera.', null],
            ],
            'Salads & Light Meals' => [
                ['Garden Salad', 260, 'Fresh greens, tomato, cucumber, and house dressing.', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=1200&q=80'],
                ['Chicken Caesar Salad', 420, 'Romaine lettuce, grilled chicken, croutons, and Caesar dressing.', 'https://images.unsplash.com/photo-1550304943-4f24f54ddde9?auto=format&fit=crop&w=1200&q=80'],
                ['Tuna Sandwich', 340, 'Tuna, herbs, and house sauce on toasted bread.', null],
            ],
            'Desserts' => [
                ['Chocolate Cake', 260, 'Moist chocolate cake slice.', 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=1200&q=80'],
                ['Red Velvet Cake', 290, 'Classic red velvet with cream frosting.', 'https://images.unsplash.com/photo-1586788680434-30d324b2d46f?auto=format&fit=crop&w=1200&q=80'],
                ['Cheesecake Cup', 320, 'Creamy cheesecake served in a cup.', null],
            ],
            'Drinks' => [
                ['Macchiato', 90, 'Classic Ethiopian macchiato.', 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=1200&q=80'],
                ['Mango Juice', 180, 'Fresh mango juice.', 'https://images.unsplash.com/photo-1622597467836-f3285f2131b8?auto=format&fit=crop&w=1200&q=80'],
                ['Mixed Juice', 220, 'Layered seasonal fruit juice.', null],
            ],
        ];

        $categorySort = 1;
        foreach ($categories as $categoryName => $items) {
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

            foreach ($items as $index => [$name, $price, $description, $imagePath]) {
                DB::table('menu_items')->updateOrInsert(
                    ['restaurant_id' => $hotelId, 'name' => $name],
                    [
                        'category_id' => $categoryId,
                        'description' => $description,
                        'price' => $price,
                        'image_path' => $imagePath,
                        'is_available' => true,
                        'is_featured' => $index === 0,
                        'sort_order' => $index + 1,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        $hotelId = DB::table('restaurants')->where('slug', 'ginashotel')->value('id');

        if (! $hotelId) {
            return;
        }

        DB::table('menu_items')->where('restaurant_id', $hotelId)->delete();
        DB::table('categories')->where('restaurant_id', $hotelId)->delete();
    }
};
