<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    private function restaurant(Request $request) { return $request->user()->restaurant; }

    public function index(Request $request)
    {
        $restaurant = $this->restaurant($request);
        return view('restaurant.categories.index', ['restaurant' => $restaurant, 'categories' => $restaurant->categories()->paginate(50)]);
    }

    public function store(Request $request)
    {
        $this->restaurant($request)->categories()->create($this->validated($request));
        return back()->with('success', 'Category added.');
    }

    public function update(Request $request, Category $category)
    {
        abort_unless($category->restaurant_id === $this->restaurant($request)->id, 403);
        $category->update($this->validated($request));
        return back()->with('success', 'Category updated.');
    }

    public function destroy(Request $request, Category $category)
    {
        abort_unless($category->restaurant_id === $this->restaurant($request)->id, 403);
        $category->delete();
        return back()->with('success', 'Category deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
