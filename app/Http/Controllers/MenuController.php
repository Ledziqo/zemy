<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;

class MenuController extends Controller
{
    public function show(string $restaurant_slug, string $table_number)
    {
        $restaurant = Restaurant::where('slug', $restaurant_slug)->where('is_active', true)
            ->with(['categories.menuItems' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
            ->firstOrFail();

        $restaurantTable = $restaurant->tables()->where('table_number', $table_number)->where('is_active', true)->firstOrFail();

        return view('menu.show', [
            'restaurant' => $restaurant,
            'table' => $restaurantTable,
            'categories' => $restaurant->categories->where('is_active', true),
        ]);
    }

    public function confirmation(string $restaurant_slug, string $table_number)
    {
        $restaurant = Restaurant::where('slug', $restaurant_slug)->firstOrFail();
        $table = $table_number;
        return view('menu.confirmation', compact('restaurant', 'table'));
    }
}
