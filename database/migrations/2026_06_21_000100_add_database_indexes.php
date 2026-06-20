<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add indexes safely - only if they don't already exist
        try { DB::statement('CREATE INDEX idx_orders_restaurant_created ON orders (restaurant_id, created_at)'); } catch (\Exception $e) {}
        try { DB::statement('CREATE INDEX idx_orders_restaurant_status ON orders (restaurant_id, status)'); } catch (\Exception $e) {}
        try { DB::statement('CREATE INDEX idx_service_requests_restaurant_status ON service_requests (restaurant_id, status)'); } catch (\Exception $e) {}
        try { DB::statement('CREATE INDEX idx_subscriptions_restaurant_starts ON subscriptions (restaurant_id, starts_at)'; } catch (\Exception $e) {}
        try { DB::statement('CREATE INDEX idx_order_items_order_id ON order_items (order_id)'); } catch (\Exception $e) {}
        try { DB::statement('CREATE INDEX idx_menu_items_restaurant_id ON menu_items (restaurant_id)'); } catch (\Exception $e) {}
        try { DB::statement('CREATE INDEX idx_categories_restaurant_id ON categories (restaurant_id)'); } catch (\Exception $e) {}
        try { DB::statement('CREATE INDEX idx_restaurant_tables_restaurant_id ON restaurant_tables (restaurant_id)'); } catch (\Exception $e) {}
    }

    public function down(): void
    {
        // Indexes are safe to leave, but we can drop them if needed
        try { DB::statement('DROP INDEX idx_orders_restaurant_created ON orders'); } catch (\Exception $e) {}
        try { DB::statement('DROP INDEX idx_orders_restaurant_status ON orders'); } catch (\Exception $e) {}
        try { DB::statement('DROP INDEX idx_service_requests_restaurant_status ON service_requests'); } catch (\Exception $e) {}
        try { DB::statement('DROP INDEX idx_subscriptions_restaurant_starts ON subscriptions'); } catch (\Exception $e) {}
        try { DB::statement('DROP INDEX idx_order_items_order_id ON order_items'); } catch (\Exception $e) {}
        try { DB::statement('DROP INDEX idx_menu_items_restaurant_id ON menu_items'); } catch (\Exception $e) {}
        try { DB::statement('DROP INDEX idx_categories_restaurant_id ON categories'); } catch (\Exception $e) {}
        try { DB::statement('DROP INDEX idx_restaurant_tables_restaurant_id ON restaurant_tables'); } catch (\Exception $e) {}
    }
};
