<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('restaurants') && ! Schema::hasColumn('restaurants', 'menu_cache_version')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->unsignedInteger('menu_cache_version')->default(1)->after('settings');
            });
        }

        $this->tryStatement('CREATE INDEX idx_orders_restaurant_id_id ON orders (restaurant_id, id)');
        $this->tryStatement('CREATE INDEX idx_orders_restaurant_status_created ON orders (restaurant_id, status, created_at)');
        $this->tryStatement('CREATE INDEX idx_orders_created_at ON orders (created_at)');
        $this->tryStatement('CREATE INDEX idx_service_requests_restaurant_id_id ON service_requests (restaurant_id, id)');
        $this->tryStatement('CREATE INDEX idx_service_requests_restaurant_status_created ON service_requests (restaurant_id, status, created_at)');
        $this->tryStatement('CREATE INDEX idx_service_requests_created_at ON service_requests (created_at)');
        $this->tryStatement('CREATE INDEX idx_guest_sessions_restaurant_table_token_active ON guest_sessions (restaurant_id, table_id, token, closed_at, expires_at)');
        $this->tryStatement('CREATE INDEX idx_guest_sessions_expires_closed ON guest_sessions (expires_at, closed_at)');
        $this->tryStatement('CREATE INDEX idx_payments_guest_session_id ON payments (guest_session_id)');
    }

    public function down(): void
    {
        $this->tryStatement('DROP INDEX idx_orders_restaurant_id_id ON orders');
        $this->tryStatement('DROP INDEX idx_orders_restaurant_status_created ON orders');
        $this->tryStatement('DROP INDEX idx_orders_created_at ON orders');
        $this->tryStatement('DROP INDEX idx_service_requests_restaurant_id_id ON service_requests');
        $this->tryStatement('DROP INDEX idx_service_requests_restaurant_status_created ON service_requests');
        $this->tryStatement('DROP INDEX idx_service_requests_created_at ON service_requests');
        $this->tryStatement('DROP INDEX idx_guest_sessions_restaurant_table_token_active ON guest_sessions');
        $this->tryStatement('DROP INDEX idx_guest_sessions_expires_closed ON guest_sessions');
        $this->tryStatement('DROP INDEX idx_payments_guest_session_id ON payments');

        if (Schema::hasTable('restaurants') && Schema::hasColumn('restaurants', 'menu_cache_version')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->dropColumn('menu_cache_version');
            });
        }
    }

    private function tryStatement(string $sql): void
    {
        try {
            DB::statement($sql);
        } catch (Throwable) {
        }
    }
};
