<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $restaurant = DB::table('restaurants')->where('slug', 'tulip-olympia')->first();
        if (! $restaurant) return;

        $base = 'uploads/menu-items/';
        $map = [
            'Espresso' => 'tulip-espresso.png', 'Double Espresso' => 'tulip-espresso.png',
            'Macchiato' => 'tulip-macchiato.png', 'Double Macchiato' => 'tulip-macchiato.png',
            'Selection of Tea' => 'tulip-tea.png', 'Tea with Coffee' => 'tulip-tea.png',
            'Tea with Milk' => 'tulip-tea.png', 'Special Tea' => 'tulip-tea.png', 'Tea with Honey' => 'tulip-tea.png',
            'Milk' => 'tulip-milk.png', 'Soft Drinks' => 'tulip-soft-drink.png',
            'Small Water (600 ml)' => 'tulip-water.png', 'Medium Water (1000 ml)' => 'tulip-water.png', 'Big Water (2000 ml)' => 'tulip-water.png',
            'Papaya' => 'tulip-papaya-juice.png', 'Watermelon' => 'tulip-watermelon-juice.png',
            'Mixed Juice' => 'tulip-mixed-juice.png', 'Avocado' => 'tulip-avocado-juice.png',
            'Banana Cake' => 'tulip-banana-cake.png', 'Cookies (1 kg)' => 'tulip-cookies.png',
        ];
        foreach ($map as $name => $file) {
            DB::table('menu_items')->where('restaurant_id', $restaurant->id)->where('name', $name)->update(['image_path' => $base.$file, 'updated_at' => now()]);
        }
    }
};
