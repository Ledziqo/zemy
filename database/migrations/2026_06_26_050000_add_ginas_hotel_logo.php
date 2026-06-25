<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('restaurants')
            ->where('slug', 'ginashotel')
            ->update([
                'logo_path' => 'uploads/restaurants/ginas-hotel-logo.svg',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('restaurants')
            ->where('slug', 'ginashotel')
            ->where('logo_path', 'uploads/restaurants/ginas-hotel-logo.svg')
            ->update([
                'logo_path' => null,
                'updated_at' => now(),
            ]);
    }
};
