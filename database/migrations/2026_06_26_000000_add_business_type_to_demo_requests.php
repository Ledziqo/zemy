<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('demo_requests') || Schema::hasColumn('demo_requests', 'business_type')) {
            return;
        }

        Schema::table('demo_requests', function (Blueprint $table) {
            $table->string('business_type')->default('restaurant')->after('restaurant_name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('demo_requests') || ! Schema::hasColumn('demo_requests', 'business_type')) {
            return;
        }

        Schema::table('demo_requests', function (Blueprint $table) {
            $table->dropColumn('business_type');
        });
    }
};
