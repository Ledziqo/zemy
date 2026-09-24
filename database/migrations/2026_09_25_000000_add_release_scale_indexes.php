<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach ([
            'CREATE INDEX idx_orders_restaurant_handled_created ON orders (restaurant_id, handled_by_profile_id, created_at)',
            'CREATE INDEX idx_orders_restaurant_payment_status ON orders (restaurant_id, payment_method, payment_status, status)',
            'CREATE INDEX idx_payments_restaurant_order ON payments (restaurant_id, order_id)',
        ] as $statement) {
            try { DB::statement($statement); } catch (\Throwable) { }
        }
    }

    public function down(): void
    {
        foreach (['idx_orders_restaurant_handled_created', 'idx_orders_restaurant_payment_status', 'idx_payments_restaurant_order'] as $index) {
            try { DB::statement("DROP INDEX {$index} ON ".($index === 'idx_payments_restaurant_order' ? 'payments' : 'orders')); } catch (\Throwable) { }
        }
    }
};
