<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Support\MenuPackageImporter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class MenuImportController extends Controller
{
    public function store(Request $request, MenuPackageImporter $importer)
    {
        $data = $request->validate([
            'restaurant_id' => ['required', 'integer', Rule::exists('restaurants', 'id')],
            'menu_package' => ['required', 'file', 'max:524288', 'extensions:zip'],
        ]);

        $restaurant = Restaurant::findOrFail($data['restaurant_id']);

        try {
            $result = $importer->import($restaurant, $request->file('menu_package'));

            return back()->with('menu_import_output', "Imported {$result['items']} menu items in {$result['categories']} categories for {$restaurant->name}. Existing menu categories and items were replaced.");
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('menu_import_output', 'Menu import failed: '.$exception->getMessage());
        }
    }
}
