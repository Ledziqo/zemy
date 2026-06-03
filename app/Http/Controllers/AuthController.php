<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Throwable;

class AuthController extends Controller
{
    public function showLogin()
    {
        $this->ensureDemoLoginReady();

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = Auth::user();
        $restaurantAccessStatus = $user->restaurant?->dashboard_access_status ?? 'active';

        return match ($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'restaurant_owner', 'staff' => $restaurantAccessStatus === 'active'
                ? redirect()->route('restaurant.dashboard')
                : redirect()->route('restaurant.access-required'),
            default => redirect()->route('restaurant.dashboard'),
        };
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function ensureDemoLoginReady(): void
    {
        try {
            if (! Schema::hasTable('restaurants') || ! Schema::hasTable('users')) {
                return;
            }

            if (! Schema::hasColumn('restaurants', 'dashboard_access_status')) {
                Schema::table('restaurants', function (Blueprint $table) {
                    $table->string('dashboard_access_status')->default('active')->after('is_active');
                });
            }

            User::updateOrCreate(
                ['email' => 'admin@zemtab.test'],
                ['name' => 'ZemTab Admin', 'password' => Hash::make('password'), 'role' => 'admin']
            );

            $restaurant = Restaurant::updateOrCreate(
                ['slug' => 'bole-bistro'],
                [
                    'name' => 'Bole Bistro Demo',
                    'phone' => '+251 911 000 000',
                    'email' => 'hello@bolebistro.test',
                    'location' => 'Bole, Addis Ababa',
                    'primary_color' => '#D89B35',
                    'is_active' => true,
                    'dashboard_access_status' => 'active',
                    'settings' => [
                        'service_charge_percentage' => 0,
                        'vat_percentage' => 0,
                        'payment_methods' => ['cash', 'telebirr', 'cbe'],
                    ],
                ]
            );

            User::updateOrCreate(
                ['email' => 'owner@bolebistro.test'],
                ['name' => 'Bole Bistro Owner', 'password' => Hash::make('password'), 'role' => 'restaurant_owner', 'restaurant_id' => $restaurant->id]
            );

            User::updateOrCreate(
                ['email' => 'staff@bolebistro.test'],
                ['name' => 'Bole Bistro Staff', 'password' => Hash::make('password'), 'role' => 'staff', 'restaurant_id' => $restaurant->id]
            );

            if (Schema::hasTable('categories') && Schema::hasTable('menu_items')) {
                $category = Category::updateOrCreate(
                    ['restaurant_id' => $restaurant->id, 'name' => 'Demo Favorites'],
                    ['sort_order' => 1, 'is_active' => true]
                );

                foreach ([
                    ['Beef Tibs', 550, 'Pan-seared beef with rosemary, jalapeno, and injera.'],
                    ['Special Burger', 420, 'Beef patty, cheese, lettuce, tomato, and house sauce.'],
                    ['Macchiato', 90, 'Classic Ethiopian macchiato.'],
                ] as $index => [$name, $price, $description]) {
                    MenuItem::updateOrCreate(
                        ['restaurant_id' => $restaurant->id, 'name' => $name],
                        [
                            'category_id' => $category->id,
                            'description' => $description,
                            'price' => $price,
                            'image_path' => null,
                            'is_available' => true,
                            'is_featured' => $index === 0,
                            'sort_order' => $index + 1,
                        ]
                    );
                }
            }

            if (Schema::hasTable('restaurant_tables')) {
                for ($i = 1; $i <= 3; $i++) {
                    RestaurantTable::updateOrCreate(
                        ['restaurant_id' => $restaurant->id, 'table_number' => (string) $i],
                        ['table_name' => 'Table '.$i, 'is_active' => true]
                    );
                }
            }

            if (Schema::hasTable('subscriptions')) {
                Subscription::updateOrCreate(
                    ['restaurant_id' => $restaurant->id, 'plan_name' => 'Pro'],
                    ['monthly_price' => 5000, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth()]
                );
            }
        } catch (Throwable) {
            // Login should still render even if the database user cannot alter or seed tables.
        }
    }
}
