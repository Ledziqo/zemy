<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('restaurant_tables', 'location_type')) {
            Schema::table('restaurant_tables', function (Blueprint $table) {
                $table->string('location_type', 20)->default('table')->after('table_number');
            });
        }

        DB::table('restaurant_tables')
            ->where(function ($query) {
                $query->where('table_number', 'like', 'restaurant-%')
                    ->orWhere('table_number', 'like', 'lobby-%');
            })
            ->update(['location_type' => 'table']);

        $hotelIds = DB::table('restaurants')
            ->where('business_type', 'hotel')
            ->pluck('id');

        if ($hotelIds->isNotEmpty()) {
            DB::table('restaurant_tables')
                ->whereIn('restaurant_id', $hotelIds)
                ->where('table_number', 'not like', 'restaurant-%')
                ->where('table_number', 'not like', 'lobby-%')
                ->update(['location_type' => 'room']);
        }

        $mixedIds = DB::table('restaurants')
            ->where('business_type', 'both')
            ->pluck('id');

        if ($mixedIds->isNotEmpty()) {
            DB::table('restaurant_tables')
                ->whereIn('restaurant_id', $mixedIds)
                ->where(function ($query) {
                    $query->where('table_name', 'like', 'Room %')
                        ->orWhere('table_name', 'like', 'Suite %');
                })
                ->update(['location_type' => 'room']);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('restaurant_tables', 'location_type')) {
            Schema::table('restaurant_tables', function (Blueprint $table) {
                $table->dropColumn('location_type');
            });
        }
    }
};
