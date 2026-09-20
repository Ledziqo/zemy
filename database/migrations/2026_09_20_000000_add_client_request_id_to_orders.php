<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'client_request_id')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->string('client_request_id', 80)->nullable()->after('guest_session_id');
                $table->unique(['restaurant_id', 'client_request_id'], 'orders_restaurant_client_request_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'client_request_id')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropUnique('orders_restaurant_client_request_unique');
                $table->dropColumn('client_request_id');
            });
        }
    }
};
