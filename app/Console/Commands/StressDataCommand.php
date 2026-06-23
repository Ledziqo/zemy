<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class StressDataCommand extends Command
{
    protected $signature = 'stress:data {action=seed : seed or cleanup} {--restaurants=25} {--tables=10} {--items=20}';
    protected $description = 'Create or remove marked fake ZemTab stress-test data';

    public function handle(): int
    {
        return $this->argument('action') === 'cleanup'
            ? $this->cleanup()
            : $this->seed();
    }

    private function seed(): int
    {
        $restaurantCount = max(1, (int) $this->option('restaurants'));
        $tableCount = max(1, (int) $this->option('tables'));
        $itemCount = max(1, (int) $this->option('items'));

        for ($r = 1; $r <= $restaurantCount; $r++) {
            $slug = sprintf('zt-stress-%03d', $r);
            $restaurant = Restaurant::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => sprintf('ZT Stress Venue %03d', $r),
                    'business_type' => 'restaurant',
                    'phone' => '+251 900 000 '.str_pad((string) $r, 3, '0', STR_PAD_LEFT),
                    'email' => "stress-{$r}@zemtab.test",
                    'location' => 'Stress Test',
                    'primary_color' => '#D22630',
                    'is_active' => true,
                    'dashboard_access_status' => 'active',
                    'settings' => [
                        'service_charge_percentage' => 0,
                        'vat_percentage' => 0,
                        'payment_methods' => ['cash'],
                    ],
                ]
            );

            User::updateOrCreate(
                ['email' => "zt-stress-staff-{$r}@zemtab.test"],
                [
                    'name' => "ZT Stress Staff {$r}",
                    'password' => Hash::make('password'),
                    'role' => 'staff',
                    'restaurant_id' => $restaurant->id,
                ]
            );

            $category = Category::updateOrCreate(
                ['restaurant_id' => $restaurant->id, 'name' => 'Stress Menu'],
                ['sort_order' => 1, 'is_active' => true]
            );

            for ($i = 1; $i <= $itemCount; $i++) {
                MenuItem::updateOrCreate(
                    ['restaurant_id' => $restaurant->id, 'name' => "Stress Item {$i}"],
                    [
                        'category_id' => $category->id,
                        'description' => 'Generated item for launch-readiness stress testing.',
                        'price' => 100 + $i,
                        'image_path' => null,
                        'is_available' => true,
                        'is_featured' => $i === 1,
                        'sort_order' => $i,
                    ]
                );
            }

            for ($t = 1; $t <= $tableCount; $t++) {
                RestaurantTable::updateOrCreate(
                    ['restaurant_id' => $restaurant->id, 'table_number' => (string) $t],
                    ['table_name' => "Stress Table {$t}", 'is_active' => true]
                );
            }

            Subscription::updateOrCreate(
                ['restaurant_id' => $restaurant->id, 'plan_name' => 'Stress Test'],
                ['monthly_price' => 2000, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth()]
            );
        }

        $this->info("Seeded {$restaurantCount} stress restaurants.");
        $this->line('Staff login pattern: zt-stress-staff-{n}@zemtab.test / password');

        return self::SUCCESS;
    }

    private function cleanup(): int
    {
        $restaurants = Restaurant::where('slug', 'like', 'zt-stress-%')->get();
        $count = $restaurants->count();

        foreach ($restaurants as $restaurant) {
            User::where('restaurant_id', $restaurant->id)->where('email', 'like', 'zt-stress-staff-%')->delete();
            $restaurant->delete();
        }

        $this->info("Removed {$count} stress restaurants.");

        return self::SUCCESS;
    }
}
