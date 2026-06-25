<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $hotelId = DB::table('restaurants')->where('slug', 'ginashotel')->value('id');

        if (! $hotelId || ! DB::getSchemaBuilder()->hasTable('staff_profiles')) {
            return;
        }

        $now = now();

        foreach ([
            ['name' => 'Owner/Manager', 'role' => 'owner_manager'],
            ['name' => 'Cashier', 'role' => 'cashier'],
            ['name' => 'Kitchen', 'role' => 'kitchen'],
        ] as $profile) {
            DB::table('staff_profiles')->updateOrInsert(
                ['restaurant_id' => $hotelId, 'role' => $profile['role']],
                [
                    'name' => $profile['name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $hotelId = DB::table('restaurants')->where('slug', 'ginashotel')->value('id');

        if (! $hotelId || ! DB::getSchemaBuilder()->hasTable('staff_profiles')) {
            return;
        }

        DB::table('staff_profiles')
            ->where('restaurant_id', $hotelId)
            ->whereIn('role', ['owner_manager', 'cashier', 'kitchen'])
            ->delete();
    }
};
