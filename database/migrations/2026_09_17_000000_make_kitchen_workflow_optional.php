<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('restaurants') || ! Schema::hasColumn('restaurants', 'kitchen_screen_enabled')) {
            return;
        }

        if (! Schema::hasTable('staff_profiles')) {
            DB::table('restaurants')->update(['kitchen_screen_enabled' => false]);
            return;
        }

        DB::table('restaurants')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('staff_profiles')
                    ->whereColumn('staff_profiles.restaurant_id', 'restaurants.id')
                    ->where('staff_profiles.role', 'kitchen')
                    ->where('staff_profiles.is_active', true);
            })
            ->update(['kitchen_screen_enabled' => false]);
    }

    public function down(): void
    {
        // Kitchen mode is an operational preference; do not re-enable it on rollback.
    }
};
