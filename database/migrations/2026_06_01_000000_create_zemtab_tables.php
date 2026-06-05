<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('business_type')->default('restaurant');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('location')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->string('primary_color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('image_path')->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('table_number');
            $table->string('table_name')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['restaurant_id', 'table_number']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('table_id')->nullable()->constrained('restaurant_tables')->nullOnDelete();
            $table->string('table_number');
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['new', 'preparing', 'served', 'paid', 'completed', 'cancelled'])->default('new');
            $table->string('payment_method')->nullable();
            $table->enum('payment_status', ['unpaid', 'pending', 'paid', 'failed', 'refunded'])->default('unpaid');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('service_charge', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_name');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('table_id')->nullable()->constrained('restaurant_tables')->nullOnDelete();
            $table->string('table_number');
            $table->enum('type', ['call_waiter', 'request_bill', 'request_water', 'other']);
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'acknowledged', 'completed'])->default('pending');
            $table->timestamps();
        });

        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('restaurant_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('location')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['new', 'contacted', 'converted', 'closed'])->default('new');
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('plan_name');
            $table->decimal('monthly_price', 10, 2)->nullable();
            $table->enum('status', ['active', 'unpaid', 'trial', 'cancelled'])->default('trial');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('method');
            $table->enum('status', ['unpaid', 'pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('demo_requests');
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('restaurant_tables');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('restaurants');
    }
};
