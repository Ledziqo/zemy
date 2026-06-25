<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hotelId = DB::table('restaurants')->where('slug', 'ginashotel')->value('id');

        if (! $hotelId) {
            return;
        }

        $photos = [
            'Ful Special' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=1200&q=80',
            'Banana Bread Slice' => 'https://images.unsplash.com/photo-1603052875302-d376b7c0638a?auto=format&fit=crop&w=1200&q=80',
            'Vegetable Penne' => 'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?auto=format&fit=crop&w=1200&q=80',
            'Meat Lovers Pizza' => 'https://images.unsplash.com/photo-1594007654729-407eedc4be65?auto=format&fit=crop&w=1200&q=80',
            'Double Cheese Burger' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=1200&q=80',
            'Grilled Chicken Plate' => 'https://images.unsplash.com/photo-1532550907401-a500c9a57435?auto=format&fit=crop&w=1200&q=80',
            'Beef Tibs' => 'https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?auto=format&fit=crop&w=1200&q=80',
            'Kitfo' => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=1200&q=80',
            'Shiro Tegabino' => 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?auto=format&fit=crop&w=1200&q=80',
            'Tuna Sandwich' => 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=1200&q=80',
            'Cheesecake Cup' => 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?auto=format&fit=crop&w=1200&q=80',
            'Mixed Juice' => 'https://images.unsplash.com/photo-1546173159-315724a31696?auto=format&fit=crop&w=1200&q=80',
        ];

        foreach ($photos as $itemName => $imagePath) {
            DB::table('menu_items')
                ->where('restaurant_id', $hotelId)
                ->where('name', $itemName)
                ->update([
                    'image_path' => $imagePath,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $hotelId = DB::table('restaurants')->where('slug', 'ginashotel')->value('id');

        if (! $hotelId) {
            return;
        }

        DB::table('menu_items')
            ->where('restaurant_id', $hotelId)
            ->whereIn('name', [
                'Ful Special',
                'Banana Bread Slice',
                'Vegetable Penne',
                'Meat Lovers Pizza',
                'Double Cheese Burger',
                'Grilled Chicken Plate',
                'Beef Tibs',
                'Kitfo',
                'Shiro Tegabino',
                'Tuna Sandwich',
                'Cheesecake Cup',
                'Mixed Juice',
            ])
            ->update([
                'image_path' => null,
                'updated_at' => now(),
            ]);
    }
};
