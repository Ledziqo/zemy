<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Staff profiles table — each restaurant can have cashier/kitchen/owner-manager profiles
        if (! Schema::hasTable('staff_profiles')) {
            Schema::create('staff_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->enum('role', ['owner_manager', 'cashier', 'kitchen'])->default('cashier');
                $table->string('password');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Add cashier tracking + order type to orders table
        if (Schema::hasTable('orders')) {
            if (! Schema::hasColumn('orders', 'handled_by_profile_id')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->foreignId('handled_by_profile_id')->nullable()->after('guest_session_id')->constrained('staff_profiles')->nullOnDelete();
                });
            }

            if (! Schema::hasColumn('orders', 'order_type')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->enum('order_type', ['dine_in', 'delivery'])->default('dine_in')->after('table_number');
                });
            }
        }

        // Create default owner_manager profile for each existing restaurant
        $restaurants = \App\Models\Restaurant::all();
        foreach ($restaurants as $restaurant) {
            $existing = \App\Models\StaffProfile::where('restaurant_id', $restaurant->id)
                ->where('role', 'owner_manager')
                ->first();

            if (! $existing) {
                \App\Models\StaffProfile::create([
                    'restaurant_id' => $restaurant->id,
                    'name' => 'Owner/Manager',
                    'role' => 'owner_manager',
                    'password' => bcrypt('password'),
                    'is_active' => true,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            if (Schema::hasColumn('orders', 'handled_by_profile_id')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropConstrainedForeignId('handled_by_profile_id');
                });
            }
            if (Schema::hasColumn('orders', 'order_type')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropColumn('order_type');
                });
            }
        }

        Schema::dropIfExists('staff_profiles');
    }
};