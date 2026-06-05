<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('restaurants', 'business_type')) {
            return;
        }

        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('business_type')->default('restaurant')->after('slug');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('restaurants', 'business_type')) {
            return;
        }

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('business_type');
        });
    }
};
