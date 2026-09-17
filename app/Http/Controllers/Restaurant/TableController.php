<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use App\Support\PublicMenuCache;
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
        $restaurant = $this->restaurant($request);
        $table = $restaurant->tables()->create($this->validated($request));
        PublicMenuCache::bump($restaurant);
        return back()->with('success', $table->locationTypeLabel().' added.');
    }

    public function update(Request $request, RestaurantTable $table)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($table->restaurant_id === $restaurant->id, 403);
        $table->update($this->validated($request));
        PublicMenuCache::bump($restaurant);
        return back()->with('success', $table->locationTypeLabel().' updated.');
    }

    public function destroy(Request $request, RestaurantTable $table)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($table->restaurant_id === $restaurant->id, 403);
        $table->delete();
        PublicMenuCache::bump($restaurant);
        return back()->with('success', $restaurant->locationLabelTitle().' deleted.');
    }

    public function qr(Request $request, RestaurantTable $table)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($table->restaurant_id === $restaurant->id, 403);

        $result = $this->buildQr($restaurant, $table);

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'inline; filename="zemtab-'.$restaurant->slug.'-'.strtolower($table->locationTypeLabel()).'-'.$table->table_number.'.svg"',
        ]);
    }

    private function buildQr($restaurant, RestaurantTable $table)
    {
        return (new Builder(
            writer: new SvgWriter(),
            data: route('menu.show', [$restaurant->slug, $table->table_number]),
            size: 500,
            margin: 20,
        ))->build();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'table_number' => ['required', 'string', 'max:50'],
            'location_type' => ['required', 'in:table,room'],
            'table_name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
