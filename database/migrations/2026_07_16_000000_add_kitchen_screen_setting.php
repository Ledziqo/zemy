<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('restaurants') && ! Schema::hasColumn('restaurants', 'kitchen_screen_enabled')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->boolean('kitchen_screen_enabled')->default(true)->after('business_type');
            });
        }

        if (Schema::hasTable('staff_profiles') && ! Schema::hasColumn('staff_profiles', 'disabled_by_kitchen_mode')) {
            Schema::table('staff_profiles', function (Blueprint $table) {
                $table->boolean('disabled_by_kitchen_mode')->default(false)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('staff_profiles') && Schema::hasColumn('staff_profiles', 'disabled_by_kitchen_mode')) {
            Schema::table('staff_profiles', function (Blueprint $table) {
                $table->dropColumn('disabled_by_kitchen_mode');
            });
        }

        if (Schema::hasTable('restaurants') && Schema::hasColumn('restaurants', 'kitchen_screen_enabled')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->dropColumn('kitchen_screen_enabled');
            });
        }
    }
};
