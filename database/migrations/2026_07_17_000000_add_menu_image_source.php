<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('menu_items') && ! Schema::hasColumn('menu_items', 'image_source_url')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->text('image_source_url')->nullable()->after('image_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('menu_items') && Schema::hasColumn('menu_items', 'image_source_url')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->dropColumn('image_source_url');
            });
        }
    }
};
