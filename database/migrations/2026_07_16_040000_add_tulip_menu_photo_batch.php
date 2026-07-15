<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $restaurant = DB::table('restaurants')->where('slug', 'tulip-olympia')->first();
        if (! $restaurant) return;

        $base = 'uploads/menu-items/';
        $photoMap = [
            'Coffee Americano' => $base.'tulip-coffee-americano.png',
            'Café Latte' => $base.'tulip-cafe-latte.png',
            'Cappuccino' => $base.'tulip-cappuccino.png',
            'Hot Chocolate' => $base.'tulip-hot-chocolate.png',
            'Traditional Coffee' => $base.'tulip-traditional-coffee.png',
            'Pizza' => $base.'tulip-pizza.png',
            'Four Season Pizza' => $base.'tulip-pizza.png',
            'Vegetarian Pizza' => $base.'tulip-pizza.png',
            'Margarita Pizza' => $base.'tulip-pizza.png',
            'Tuna Pizza' => $base.'tulip-pizza.png',
            'Orange Juice' => $base.'tulip-orange-juice.png',
            'Papaya' => $base.'tulip-orange-juice.png',
            'Watermelon' => $base.'tulip-orange-juice.png',
            'Mixed Juice' => $base.'tulip-orange-juice.png',
            'Avocado' => $base.'tulip-orange-juice.png',
            'Carrot Cake' => $base.'tulip-cake.png',
            'English Cake' => $base.'tulip-cake.png',
            'Banana Cake' => $base.'tulip-cake.png',
            'Soft Cake' => $base.'tulip-cake.png',
            'Cookies (1 kg)' => $base.'tulip-cake.png',
            'Torta Cake' => $base.'tulip-cake.png',
        ];

        foreach ($photoMap as $name => $path) {
            DB::table('menu_items')->where('restaurant_id', $restaurant->id)->where(function ($query) use ($name) {
                $query->where('name', $name)->orWhere('name', $name.' (Glass)')->orWhere('name', $name.' (Bottle)');
            })->update(['image_path' => $path, 'updated_at' => now()]);
        }

        $families = [
            'Beer' => $base.'tulip-beer.png',
            'Wines' => $base.'tulip-red-wine.png',
            'Aperitif' => $base.'tulip-red-wine.png',
            'Gin' => $base.'tulip-gin.png',
            'Vodka' => $base.'tulip-gin.png',
            'Liquor' => $base.'tulip-whisky.png',
            'Rum' => $base.'tulip-whisky.png',
            'Regular Blended Whisky' => $base.'tulip-whisky.png',
            'Deluxe Whisky' => $base.'tulip-whisky.png',
            'Single Malt Whisky' => $base.'tulip-whisky.png',
            'Tequila' => $base.'tulip-gin.png',
            'Cognac & Brandy' => $base.'tulip-whisky.png',
        ];
        foreach ($families as $category => $path) {
            $categoryId = DB::table('categories')->where('restaurant_id', $restaurant->id)->where('name', $category)->value('id');
            if ($categoryId) DB::table('menu_items')->where('restaurant_id', $restaurant->id)->where('category_id', $categoryId)->update(['image_path' => $path, 'updated_at' => now()]);
        }
    }
};
