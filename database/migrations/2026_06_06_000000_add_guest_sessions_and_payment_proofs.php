<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('guest_sessions')) {
            Schema::create('guest_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('table_id')->nullable()->constrained('restaurant_tables')->nullOnDelete();
                $table->string('table_number');
                $table->string('token', 96)->unique();
                $table->timestamp('expires_at')->index();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamp('closed_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'guest_session_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('guest_session_id')->nullable()->after('table_id')->constrained('guest_sessions')->nullOnDelete();
            });
        }

        if (Schema::hasTable('service_requests') && ! Schema::hasColumn('service_requests', 'guest_session_id')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->foreignId('guest_session_id')->nullable()->after('table_id')->constrained('guest_sessions')->nullOnDelete();
            });
        }

        if (Schema::hasTable('payments') && ! Schema::hasColumn('payments', 'guest_session_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreignId('guest_session_id')->nullable()->after('order_id')->constrained('guest_sessions')->nullOnDelete();
                $table->string('proof_image_path')->nullable()->after('reference');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'guest_session_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('guest_session_id');
                $table->dropColumn('proof_image_path');
            });
        }

        if (Schema::hasTable('service_requests') && Schema::hasColumn('service_requests', 'guest_session_id')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->dropConstrainedForeignId('guest_session_id');
            });
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'guest_session_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('guest_session_id');
            });
        }

        Schema::dropIfExists('guest_sessions');
    }
};
