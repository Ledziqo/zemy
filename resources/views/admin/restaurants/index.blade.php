@extends('layouts.dashboard', ['heading' => 'Restaurants & Hotels'])

@section('content')
@php($hasDashboardAccessStatus = \Illuminate\Support\Facades\Schema::hasColumn('restaurants', 'dashboard_access_status'))
@php($hasBusinessType = \Illuminate\Support\Facades\Schema::hasColumn('restaurants', 'business_type'))
<div class="mb-6 rounded-md border border-zem-border bg-zem-card p-4">
    <h2 class="mb-3 font-display text-lg font-bold">Add new account</h2>
    <form method="post" action="{{ route('admin.restaurants.store') }}" class="grid gap-3 md:grid-cols-4">
        @csrf
        <input name="name" required placeholder="Name" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <input name="slug" required placeholder="slug" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <select name="business_type" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2" @disabled(! $hasBusinessType)>
            <option value="restaurant">Restaurant</option>
            <option value="hotel">Hotel</option>
            <option value="both">Restaurant + Hotel</option>
        </select>
        <label class="flex items-center gap-2 rounded-md border border-zem-border px-3 py-2"><input name="kitchen_screen_enabled" type="checkbox" value="1"> Kitchen screen</label>
        <input name="phone" placeholder="Phone" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <input name="email" type="email" placeholder="Owner login email" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <input name="owner_password" type="password" placeholder="Owner login password" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <input name="location" placeholder="Location" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <input name="subscription_starts_at" type="date" placeholder="Subscription start date" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <input name="monthly_price" type="number" step="0.01" placeholder="Monthly price (ETB)" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <label class="flex items-center gap-2"><input name="is_active" type="checkbox" value="1" checked> Public active</label>
        <input type="hidden" name="dashboard_access_status" value="active">
        @unless($hasBusinessType)
            <p class="text-sm text-red-200 md:col-span-4">Run migrations to enable hotel accounts.</p>
        @endunless
        <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white md:col-span-4">Create account</button>
    </form>
</div>

<div class="grid gap-3">
    @foreach($restaurants as $restaurant)
        @php($subscription = $restaurant->subscriptions->sortByDesc('created_at')->first())
        @php($owner = $restaurant->users->first())
        @php($subStatusColors = ['active' => 'bg-green-100 text-green-700 border-green-300', 'unpaid' => 'bg-red-100 text-red-700 border-red-300', 'trial' => 'bg-zem-gold/20 text-zem-gold border-zem-gold/40', 'cancelled' => 'bg-gray-100 text-gray-600 border-gray-300'])
        @php($accessStatusColors = ['active' => 'bg-green-100 text-green-700 border-green-300', 'payment_required' => 'bg-zem-gold/20 text-zem-gold border-zem-gold/40', 'revoked' => 'bg-red-100 text-red-700 border-red-300'])
        <article class="rounded-md border border-zem-border bg-zem-card p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-display text-lg font-bold">{{ $restaurant->name }}</h3>
                        <span class="rounded-full border border-zem-border bg-zem-soft px-3 py-1 text-xs font-bold text-zem-cream">{{ $restaurant->businessTypeLabel() }}</span>
                        <span class="rounded-full border border-zem-border bg-zem-soft px-3 py-1 text-xs font-bold text-zem-cream">{{ $restaurant->kitchenScreenEnabled() ? 'Kitchen screen' : 'Worker-only' }}</span>
                        <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $subStatusColors[$subscription?->status ?? 'trial'] ?? 'border-zem-border text-zem-muted' }}">{{ $subscription?->status ?? 'trial' }}</span>
                        <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $accessStatusColors[$restaurant->dashboard_access_status ?? 'active'] ?? 'border-zem-border text-zem-muted' }}">{{ $restaurant->dashboard_access_status ?? 'active' }}</span>
                    </div>
                    <p class="mt-2 text-sm text-zem-muted">{{ $restaurant->location ?: 'No location' }} - {{ number_format($restaurant->orders_count) }} orders - Login: {{ $owner?->email ?? 'No login account yet' }}</p>
                </div>
                <a href="{{ route('menu.show', [$restaurant->slug, 1]) }}" target="_blank" class="rounded-md border border-zem-border px-4 py-2 text-sm font-bold">View menu</a>
            </div>
            <details class="mt-3 rounded-md border border-zem-border bg-zem-bg p-3">
                <summary class="cursor-pointer text-sm font-bold text-zem-cream">Edit account</summary>
                <form method="post" action="{{ route('admin.restaurants.update', $restaurant) }}" class="mt-3 grid gap-3 md:grid-cols-6">
                    @csrf @method('PATCH')
                    <input name="name" value="{{ $restaurant->name }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <input name="slug" value="{{ $restaurant->slug }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <select name="business_type" class="rounded-md border border-zem-border bg-zem-card px-3 py-2" @disabled(! $hasBusinessType)>
                        @foreach(['restaurant' => 'Restaurant', 'hotel' => 'Hotel', 'both' => 'Restaurant + Hotel'] as $value => $label)
                            <option value="{{ $value }}" @selected(($restaurant->business_type ?? 'restaurant') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <label class="flex items-center gap-2 rounded-md border border-zem-border px-3 py-2"><input name="kitchen_screen_enabled" type="checkbox" value="1" @checked($restaurant->kitchenScreenEnabled())> Kitchen screen</label>
                    <input name="phone" value="{{ $restaurant->phone }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <input name="email" value="{{ $restaurant->email }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <input name="location" value="{{ $restaurant->location }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <label class="flex items-center gap-2"><input name="is_active" type="checkbox" value="1" @checked($restaurant->is_active)> Public active</label>
                    <select name="subscription_status" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                        @foreach(['active' => 'Paid / active', 'unpaid' => 'Unpaid', 'trial' => 'Trial', 'cancelled' => 'Cancelled'] as $value => $label)
                            <option value="{{ $value }}" @selected(($subscription?->status ?? 'trial') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="dashboard_access_status" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                        <option value="active" @selected(($restaurant->dashboard_access_status ?? 'active') === 'active')>Dashboard active</option>
                        <option value="payment_required" @selected(($restaurant->dashboard_access_status ?? 'active') === 'payment_required')>Payment required</option>
                        <option value="revoked" @selected(($restaurant->dashboard_access_status ?? 'active') === 'revoked')>Revoked</option>
                    </select>
                    @unless($hasDashboardAccessStatus)
                        <p class="text-sm text-red-200 md:col-span-6">Run migrations to enable dashboard access controls.</p>
                    @endunless
                    <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white md:col-span-6">Save changes</button>
                </form>
                <form method="post" action="{{ route('admin.restaurants.destroy', $restaurant) }}" class="mt-3" onsubmit="return confirm('Delete {{ $restaurant->name }}? This will permanently delete all menu items, orders, tables, users, and subscriptions. This CANNOT be undone.');">
                    @csrf @method('DELETE')
                    <button class="w-full rounded-md border border-red-300 bg-red-50 px-4 py-2 text-sm font-bold text-red-700 transition hover:border-red-500 hover:bg-red-100">Delete {{ $restaurant->businessTypeLabel() }} permanently</button>
                </form>

                <form method="post" action="{{ route('admin.restaurants.password.update', $restaurant) }}" class="mt-3 border-t border-zem-border pt-3" data-password-form>
                    @csrf @method('PATCH')
                    <button type="button" class="rounded-md border border-zem-gold px-4 py-2 text-sm font-bold text-zem-gold transition hover:bg-zem-gold hover:text-white" data-password-toggle>Change password</button>
                    <div class="mt-3 hidden grid gap-3 md:grid-cols-[1fr_auto]" data-password-fields>
                        <input name="password" type="password" required minlength="8" placeholder="New login password" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2" disabled data-password-input>
                        <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Save password</button>
                    </div>
                </form>

                <div class="mt-3 border-t border-zem-border pt-3">
                    <h4 class="text-sm font-bold text-zem-cream">Staff profiles</h4>
                    <form method="post" action="{{ route('admin.restaurants.staff-profiles.store', $restaurant) }}" class="mt-3 grid gap-3 md:grid-cols-5">
                        @csrf
                        <input name="name" required placeholder="Staff name" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
                        <select name="role" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
                            <option value="owner_manager">Owner/Manager</option>
                            <option value="cashier">Cashier</option>
                            <option value="kitchen">Kitchen</option>
                        </select>
                        <input name="password" type="password" required minlength="4" placeholder="Profile password" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
                        <label class="flex items-center gap-2"><input name="is_active" type="checkbox" value="1" checked> Active</label>
                        <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Add staff</button>
                    </form>

                    <div class="mt-3 grid gap-2">
                        @forelse($restaurant->staffProfiles->sortBy('name') as $profile)
                            <form method="post" action="{{ route('admin.restaurants.staff-profiles.update', [$restaurant, $profile]) }}" class="grid gap-2 rounded-md border border-zem-border bg-zem-soft p-3 md:grid-cols-6">
                                @csrf @method('PATCH')
                                <input name="name" value="{{ $profile->name }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                                @if($profile->role === 'owner_manager')
                                    <input type="hidden" name="role" value="owner_manager">
                                    <div class="rounded-md border border-zem-border bg-zem-card px-3 py-2 text-sm font-bold text-zem-muted">{{ $profile->roleLabel() }}</div>
                                @else
                                    <select name="role" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                                        <option value="cashier" @selected($profile->role === 'cashier')>Cashier</option>
                                        <option value="kitchen" @selected($profile->role === 'kitchen')>Kitchen</option>
                                    </select>
                                @endif
                                <input name="password" type="password" minlength="4" placeholder="New profile password optional" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                                <label class="flex items-center gap-2"><input name="is_active" type="checkbox" value="1" @checked($profile->is_active)> Active</label>
                                <button class="rounded-md bg-zem-gold px-4 py-2 text-sm font-bold text-white">Save</button>
                                <button form="delete-staff-profile-{{ $profile->id }}" class="rounded-md border border-red-400 px-4 py-2 text-sm font-bold text-red-600" onclick="return confirm('Delete {{ $profile->name }}? Owner/Manager profiles can only be deleted when another Owner/Manager remains.')">Delete</button>
                            </form>
                            <form id="delete-staff-profile-{{ $profile->id }}" method="post" action="{{ route('admin.restaurants.staff-profiles.destroy', [$restaurant, $profile]) }}" class="hidden">
                                @csrf @method('DELETE')
                            </form>
                        @empty
                            <p class="rounded-md border border-zem-border bg-zem-soft px-3 py-2 text-sm text-zem-muted">No staff profiles yet.</p>
                        @endforelse
                    </div>
                </div>
            </details>
        </article>
    @endforeach
</div>
<div class="mt-5">{{ $restaurants->links() }}</div>
<script>
    document.addEventListener('click', function(event) {
        const toggle = event.target.closest('[data-password-toggle]');
        if (! toggle) return;
        const form = toggle.closest('[data-password-form]');
        const fields = form.querySelector('[data-password-fields]');
        const input = form.querySelector('[data-password-input]');
        fields.classList.toggle('hidden');
        input.disabled = fields.classList.contains('hidden');
        if (! input.disabled) input.focus();
    });
function filterRestaurants(query) {
        query = query.toLowerCase();
        document.querySelectorAll('#restaurant-list > article').forEach(card => {
            const name = card.querySelector('h3')?.textContent?.toLowerCase() || '';
            card.style.display = name.includes(query) ? '' : 'none';
        });
    }
</script>
@endsection
