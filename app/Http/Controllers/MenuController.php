<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Support\GuestVisitManager;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function show(Request $request, GuestVisitManager $visits, string $restaurant_slug, string $table_number)
    {
        $restaurant = Restaurant::where('slug', $restaurant_slug)->where('is_active', true)
            ->with(['categories.menuItems' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->firstOrFail();

        $restaurantTable = $restaurant->tables()->where('table_number', $table_number)->where('is_active', true)->firstOrFail();
        $visit = $visits->resolve($request, $restaurant, $restaurantTable);
        $visit->load([
            'orders.items',
            'serviceRequests' => fn ($query) => $query->latest(),
            'payments' => fn ($query) => $query->latest(),
        ]);

        return response()->view('menu.show', [
            'restaurant' => $restaurant,
            'table' => $restaurantTable,
            'categories' => $restaurant->categories->where('is_active', true),
            'visit' => $visit,
        ])->withCookie($visits->cookie($visit));
    }

    public function confirmation(Request $request, GuestVisitManager $visits, string $restaurant_slug, string $table_number)
    {
        $restaurant = Restaurant::where('slug', $restaurant_slug)->firstOrFail();
        $restaurantTable = $restaurant->tables()->where('table_number', $table_number)->firstOrFail();
        $visit = $visits->resolve($request, $restaurant, $restaurantTable);
        $table = $table_number;
        return response()->view('menu.confirmation', compact('restaurant', 'table', 'visit'))
            ->withCookie($visits->cookie($visit));
    }
}
