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
            'items' => $restaurant->menuItems()
                ->with('category')
                ->join('categories', 'categories.id', '=', 'menu_items.category_id')
                ->select('menu_items.*')
                ->orderBy('categories.sort_order')
                ->orderBy('categories.id')
                ->orderBy('menu_items.sort_order')
                ->orderBy('menu_items.id')
                ->paginate(50),
            'categories' => $restaurant->categories,
            'item' => new MenuItem(['is_available' => true]),
        ]);
    }

    public function store(Request $request)
    {
        $restaurant = $this->restaurant($request);
        $data = $this->validated($request, $restaurant->id);
        $data['sort_order'] ??= ((int) $restaurant->menuItems()->max('sort_order')) + 10;
        $restaurant->menuItems()->create($data);
        $this->clearMenuCache($request->user()->restaurant);
                return back()->with('success', 'Menu item added.');
    }

    public function update(Request $request, MenuItem $menuItem)
    {
        $restaurant = $this->restaurant($request);
        abort_unless($menuItem->restaurant_id === $restaurant->id, 403);
        $menuItem->update($this->validated($request, $restaurant->id));
        $this->clearMenuCache($request->user()->restaurant);
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

        $this->clearMenuCache($request->user()->restaurant);
                return back()->with('success', 'Menu item order saved.');
    }

    public function toggleAvailability(Request $request, MenuItem $menuItem)
    {
        abort_unless($menuItem->restaurant_id === $this->restaurant($request)->id, 403);

        $menuItem->update(['is_available' => ! $menuItem->is_available]);

        $this->clearMenuCache($request->user()->restaurant);
                return back()->with('success', $menuItem->is_available ? 'Item marked available.' : 'Item marked unavailable.');
    }

    public function removePhoto(Request $request, MenuItem $menuItem)
    {
        abort_unless($menuItem->restaurant_id === $this->restaurant($request)->id, 403);

        $menuItem->update(['image_path' => null]);

        $this->clearMenuCache($request->user()->restaurant);
                return back()->with('success', 'Menu item photo removed.');
    }

    public function destroy(Request $request, MenuItem $menuItem)
    {
        abort_unless($menuItem->restaurant_id === $this->restaurant($request)->id, 403);
        $menuItem->delete();
        $this->clearMenuCache($request->user()->restaurant);
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
            $imagePath = $this->storeCroppedImage($request->input('cropped_image'));
            if ($imagePath) {
                $data['image_path'] = $imagePath;
            }
        } elseif ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
            $destination = public_path('uploads/menu-items');
            if (! is_dir($destination)) {
                mkdir($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $data['image_path'] = 'uploads/menu-items/'.$filename;
        }

        unset($data['image'], $data['cropped_image']);

        return $data + [
            'restaurant_id' => $restaurantId,
            'is_available' => $request->boolean('is_available'),
            'is_featured' => $request->boolean('is_featured'),
        ];
    }

    private function clearMenuCache($restaurant): void
    {
        try {
            \Illuminate\Support\Facades\Cache::forget("public_menu:{$restaurant->slug}:1");
            // Clear all table caches for this restaurant
            foreach ($restaurant->tables as $table) {
                \Illuminate\Support\Facades\Cache::forget("public_menu:{$restaurant->slug}:{$table->table_number}");
            }
        } catch (\Exception $e) {}
    }

    private function storeCroppedImage(string $dataUrl): ?string
    {
        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $dataUrl, $matches)) {
            return null;
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $base64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $binary = base64_decode($base64, true);

        if ($binary === false) {
            return null;
        }

        if (strlen($binary) > 4 * 1024 * 1024 || @getimagesizefromstring($binary) === false) {
            abort(422, 'The cropped image must be a valid image no larger than 4 MB.');
        }

        $destination = public_path('uploads/menu-items');
        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $filename = Str::uuid().'.'.$extension;
        file_put_contents($destination.DIRECTORY_SEPARATOR.$filename, $binary);

        return 'uploads/menu-items/'.$filename;
    }
}
