<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;

class TableController extends Controller
{
    private function restaurant(Request $request) { return $request->user()->restaurant; }

    public function index(Request $request)
    {
        $restaurant = $this->restaurant($request);
        return view('restaurant.tables.index', ['restaurant' => $restaurant, 'tables' => $restaurant->tables()->orderByRaw('CAST(table_number AS UNSIGNED)')->paginate(50)]);
    }

    public function store(Request $request)
    {
        $this->restaurant($request)->tables()->create($this->validated($request));
        return back()->with('success', 'Table added.');
    }

    public function update(Request $request, RestaurantTable $table)
    {
        abort_unless($table->restaurant_id === $this->restaurant($request)->id, 403);
        $table->update($this->validated($request));
        return back()->with('success', 'Table updated.');
    }

    public function destroy(Request $request, RestaurantTable $table)
    {
        abort_unless($table->restaurant_id === $this->restaurant($request)->id, 403);
        $table->delete();
        return back()->with('success', 'Table deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'table_number' => ['required', 'string', 'max:50'],
            'table_name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
