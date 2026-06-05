<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function index()
    {
        return view('admin.restaurants.index', [
            'restaurants' => Restaurant::with(['subscriptions', 'users'])->withCount('orders')->latest()->paginate(50),
            'owners' => User::whereIn('role', ['restaurant_owner', 'staff'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $ownerPassword = $data['owner_password'] ?? null;
        unset($data['owner_password'], $data['subscription_status']);

        if (! Schema::hasColumn('restaurants', 'dashboard_access_status')) {
            unset($data['dashboard_access_status']);
        }

        if (! Schema::hasColumn('restaurants', 'business_type')) {
            unset($data['business_type']);
        }

        DB::transaction(function () use ($data, $ownerPassword) {
            $restaurant = Restaurant::create($data);

            if ($ownerPassword && $restaurant->email) {
                User::create([
                    'name' => $restaurant->name.' Owner',
                    'email' => $restaurant->email,
                    'password' => $ownerPassword,
                    'role' => 'restaurant_owner',
                    'restaurant_id' => $restaurant->id,
                ]);
            }
        });

        return back()->with('success', $ownerPassword ? 'Restaurant and owner login created.' : 'Restaurant created.');
    }

    public function update(Request $request, Restaurant $restaurant)
    {
        $data = $this->validated($request, $restaurant->id);
        $subscriptionStatus = $data['subscription_status'] ?? null;
        unset($data['subscription_status'], $data['owner_password']);

        if (! Schema::hasColumn('restaurants', 'dashboard_access_status')) {
            unset($data['dashboard_access_status']);
        }

        if (! Schema::hasColumn('restaurants', 'business_type')) {
            unset($data['business_type']);
        }

        $restaurant->update($data);

        if ($subscriptionStatus) {
            $restaurant->subscriptions()->updateOrCreate(
                ['plan_name' => 'Pro'],
                [
                    'monthly_price' => $restaurant->subscriptions()->latest()->first()?->monthly_price ?? 5000,
                    'status' => $subscriptionStatus,
                    'starts_at' => now(),
                    'ends_at' => $subscriptionStatus === 'active' ? now()->addMonth() : null,
                ]
            );

            if (
                Schema::hasColumn('restaurants', 'dashboard_access_status')
                && $subscriptionStatus === 'unpaid'
                && ($restaurant->dashboard_access_status ?? 'active') === 'active'
            ) {
                $restaurant->update(['dashboard_access_status' => 'payment_required']);
            }
        }

        return back()->with('success', 'Restaurant updated.');
    }

    public function updatePassword(Request $request, Restaurant $restaurant)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $user = $restaurant->users()->first();

        if (! $user) {
            if (! $restaurant->email) {
                throw ValidationException::withMessages([
                    'password' => 'Add an owner login email before creating a password for this restaurant.',
                ]);
            }

            if (User::where('email', $restaurant->email)->exists()) {
                throw ValidationException::withMessages([
                    'password' => 'This restaurant email is already used by another login account.',
                ]);
            }

            $user = $restaurant->users()->create([
                'name' => $restaurant->name.' Owner',
                'email' => $restaurant->email,
                'password' => $data['password'],
                'role' => 'restaurant_owner',
            ]);
        } else {
            $user->update(['password' => $data['password']]);
        }

        return back()->with('success', 'Restaurant login password updated.');
    }

    public function destroy(Restaurant $restaurant)
    {
        $restaurant->delete();
        return back()->with('success', 'Restaurant deleted.');
    }

    private function validated(Request $request, ?int $restaurantId = null): array
    {
        $emailRules = ['nullable', 'email', 'max:255'];
        if ($restaurantId === null) {
            $emailRules[] = 'required_with:owner_password';
            $emailRules[] = Rule::unique('users', 'email');
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('restaurants', 'slug')->ignore($restaurantId)],
            'business_type' => [Rule::requiredIf(Schema::hasColumn('restaurants', 'business_type')), 'nullable', Rule::in(Restaurant::BUSINESS_TYPES)],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => $emailRules,
            'owner_password' => [$restaurantId === null ? 'nullable' : 'prohibited', 'string', 'min:8', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'dashboard_access_status' => ['nullable', Rule::in(Restaurant::DASHBOARD_ACCESS_STATUSES)],
            'subscription_status' => ['nullable', 'in:active,unpaid,trial,cancelled'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
