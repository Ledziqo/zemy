<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Support\ImageOptimizer;
use App\Support\PublicMenuCache;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    private function restaurant(Request $request) { return $request->user()->restaurant; }

    public function index(Request $request)
    {
        $restaurant = $this->restaurant($request);
        $categoryId = $request->integer('category_id') ?: null;
        $categories = $restaurant->categories()->orderBy('sort_order')->orderBy('name')->get();
        return view('restaurant.menu_items.index', [
            'restaurant' => $restaurant,
            'items' => $restaurant->menuItems()
                ->with('category')
                ->join('categories', 'categories.id', '=', 'menu_items.category_id')
                ->select('menu_items.*')
                ->when($categoryId, fn ($query) => $query->where('menu_items.category_id', $categoryId))
                ->orderBy('categories.sort_order')
                ->orderBy('categories.id')
                ->orderBy('menu_items.sort_order')
                ->orderBy('menu_items.id')
                ->paginate(50)
                ->withQueryString(),
            'categories' => $categories,
            'selectedCategoryId' => $categoryId,
            'item' => new MenuItem(['is_available' => true]),
        ]);
    }

    public function store(Request $request)
    {
        $restaurant = $this->restaurant($request);
        $data = $this->validated($request, $restaurant->id);
        $data['sort_order'] ??= ((int) $restaurant->menuItems()->max('sort_order')) + 10;
        $restaurant->menuItems()->create($data);
        PublicMenuCache::bump($restaurant);
                return back()->with('success', 'Menu item added.');
    }

    public function update(Request $request, MenuItem $menuItem)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($menuItem->restaurant_id === $restaurant->id, 403);
        $menuItem->update($this->validated($request, $restaurant->id));
        PublicMenuCache::bump($restaurant);
                return back()->with('success', 'Menu item updated.');
    }

    public function reorder(Request $request)
    {
        $restaurant = $this->restaurant($request);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['integer'],
        ]);

        $items = $restaurant->menuItems()
            ->whereIn('id', $data['items'])
            ->get()
            ->keyBy('id');

        abort_unless($items->count() === count(array_unique($data['items'])), 403);

        foreach (array_values($data['items']) as $index => $itemId) {
            $items[$itemId]->update(['sort_order' => ($index + 1) * 10]);
        }

        PublicMenuCache::bump($restaurant);
                return back()->with('success', 'Menu item order saved.');
    }

    public function toggleAvailability(Request $request, MenuItem $menuItem)
    {
        abort_unless($menuItem->restaurant_id === $this->restaurant($request)->id, 403);

        $menuItem->update(['is_available' => ! $menuItem->is_available]);

        PublicMenuCache::bump($this->restaurant($request));
                return back()->with('success', $menuItem->is_available ? 'Item marked available.' : 'Item marked unavailable.');
    }

    public function removePhoto(Request $request, MenuItem $menuItem)
    {
        abort_unless($menuItem->restaurant_id === $this->restaurant($request)->id, 403);

        $menuItem->update(['image_path' => null]);

        PublicMenuCache::bump($this->restaurant($request));
                return back()->with('success', 'Menu item photo removed.');
    }

    public function destroy(Request $request, MenuItem $menuItem)
    {
        abort_unless($menuItem->restaurant_id === $this->restaurant($request)->id, 403);
        $menuItem->delete();
        PublicMenuCache::bump($this->restaurant($request));
                return back()->with('success', 'Menu item deleted.');
    }

    private function validated(Request $request, int $restaurantId): array
    {
        $data = $request->validate([
            'category_id' => ['required', \Illuminate\Validation\Rule::exists('categories', 'id')->where('restaurant_id', $restaurantId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
            'cropped_image' => ['nullable', 'string', 'max:5600000'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_available' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        if ($request->filled('cropped_image')) {
            $imagePath = ImageOptimizer::storeDataUrl($request->input('cropped_image'), 'menu-items', 1200);
            if ($imagePath) {
                $data['image_path'] = $imagePath;
            }
        } elseif ($request->hasFile('image')) {
            $data['image_path'] = ImageOptimizer::storeUpload($request->file('image'), 'menu-items', 1200);
        }

        unset($data['image'], $data['cropped_image']);

        return $data + [
            'restaurant_id' => $restaurantId,
            'is_available' => $request->boolean('is_available'),
            'is_featured' => $request->boolean('is_featured'),
        ];
    }

}
