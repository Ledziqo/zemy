<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;

class TableController extends Controller
{
    private function restaurant(Request $request) { return $request->user()->restaurant; }

    public function index(Request $request)
    {
        $restaurant = $this->restaurant($request);
        return view('restaurant.tables.index', ['restaurant' => $restaurant, 'tables' => $restaurant->tables()->orderByRaw('CAST(table_number AS UNSIGNED)')->paginate(50)]);
    }

    public function setupPack(Request $request)
    {
        $restaurant = $this->restaurant($request);

        return view('restaurant.tables.setup_pack', [
            'restaurant' => $restaurant,
            'tables' => $restaurant->tables()->where('is_active', true)->orderByRaw('CAST(table_number AS UNSIGNED)')->get(),
        ]);
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

    public function qr(Request $request, RestaurantTable $table)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($table->restaurant_id === $restaurant->id, 403);

        $result = (new Builder(
            writer: new SvgWriter(),
            data: route('menu.show', [$restaurant->slug, $table->table_number]),
            size: 360,
            margin: 18,
        ))->build();

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'inline; filename="zemtab-'.$restaurant->slug.'-table-'.$table->table_number.'.svg"',
        ]);
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
