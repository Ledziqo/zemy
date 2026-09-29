<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Support\LocationPackageImporter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class LocationImportController extends Controller
{
    public function template()
    {
        $csv = "number,type,name,is_active\r\n".
            "101,room,Room 101,1\r\n".
            "T01,table,Restaurant Table 1,1\r\n".
            "L01,table,Lobby Table 1,1\r\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="zemtab-room-table-import-template.csv"',
        ]);
    }

    public function store(Request $request, LocationPackageImporter $importer)
    {
        $data = $request->validate([
            'restaurant_id' => ['required', 'integer', Rule::exists('restaurants', 'id')],
            'locations_file' => ['required', 'file', 'max:5120', 'extensions:csv,txt'],
        ]);

        $restaurant = Restaurant::findOrFail($data['restaurant_id']);
        try {
            $result = $importer->import($restaurant, $request->file('locations_file'));

            return back()->with('location_import_output', "Imported {$result['total']} locations for {$restaurant->name}: {$result['added']} added, {$result['updated']} updated. Existing QR codes/orders were preserved; locations omitted from the CSV were left unchanged.");
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('location_import_output', 'Room/table import failed: '.$exception->getMessage());
        }
    }
}
