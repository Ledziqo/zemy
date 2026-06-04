<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MenuItemController extends Controller
{
    private function restaurant(Request $request) { return $request->user()->restaurant; }

    public function index(Request $request)
    {
        $restaurant = $this->restaurant($request);
        return view('restaurant.menu_items.index', [
            'restaurant' => $restaurant,
            'items' => $restaurant->menuItems()->with('category')->orderBy('category_id')->orderBy('sort_order')->paginate(50),
            'categories' => $restaurant->categories,
            'item' => new MenuItem(['is_available' => true]),
        ]);
    }

    public function store(Request $request)
    {
        $restaurant = $this->restaurant($request);
        $data = $this->validated($request, $restaurant->id);
        $restaurant->menuItems()->create($data);
        return back()->with('success', 'Menu item added.');
    }

    public function update(Request $request, MenuItem $menuItem)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($menuItem->restaurant_id === $restaurant->id, 403);
        $menuItem->update($this->validated($request, $restaurant->id));
        return back()->with('success', 'Menu item updated.');
    }

    public function destroy(Request $request, MenuItem $menuItem)
    {
        abort_unless($menuItem->restaurant_id === $this->restaurant($request)->id, 403);
        $menuItem->delete();
        return back()->with('success', 'Menu item deleted.');
    }

    private function validated(Request $request, int $restaurantId): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_available' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
            $destination = public_path('uploads/menu-items');
            if (! is_dir($destination)) {
                mkdir($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $data['image_path'] = 'uploads/menu-items/'.$filename;
        }

        unset($data['image']);

        return $data + [
            'restaurant_id' => $restaurantId,
            'is_available' => $request->boolean('is_available'),
            'is_featured' => $request->boolean('is_featured'),
        ];
    }
}
