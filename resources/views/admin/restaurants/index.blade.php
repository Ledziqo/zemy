@extends('layouts.dashboard', ['heading' => 'Restaurants'])

@section('content')
@php($hasDashboardAccessStatus = \Illuminate\Support\Facades\Schema::hasColumn('restaurants', 'dashboard_access_status'))
<form method="post" action="{{ route('admin.restaurants.store') }}" class="mb-6 grid gap-3 rounded-md border border-zem-border bg-zem-card p-4 md:grid-cols-4">@csrf<input name="name" required placeholder="Name" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2"><input name="slug" required placeholder="slug" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2"><input name="phone" placeholder="Phone" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2"><input name="email" placeholder="Email" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2"><input name="location" placeholder="Location" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2"><label class="flex items-center gap-2"><input name="is_active" type="checkbox" value="1" checked> Public active</label><input type="hidden" name="dashboard_access_status" value="active"><button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white md:col-span-2">Create restaurant</button></form>
<div class="grid gap-3">
@foreach($restaurants as $restaurant)
    @php($subscription = $restaurant->subscriptions->sortByDesc('created_at')->first())
    <form method="post" action="{{ route('admin.restaurants.update', $restaurant) }}" class="grid gap-3 rounded-md border border-zem-border bg-zem-card p-4 md:grid-cols-6">
        @csrf @method('PATCH')
        <input name="name" value="{{ $restaurant->name }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <input name="slug" value="{{ $restaurant->slug }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <input name="phone" value="{{ $restaurant->phone }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <input name="email" value="{{ $restaurant->email }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <input name="location" value="{{ $restaurant->location }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <label class="flex items-center gap-2"><input name="is_active" type="checkbox" value="1" @checked($restaurant->is_active)> Public active</label>
        <select name="subscription_status" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
            @foreach(['active' => 'Paid / active', 'unpaid' => 'Unpaid', 'trial' => 'Trial', 'cancelled' => 'Cancelled'] as $value => $label)
                <option value="{{ $value }}" @selected(($subscription?->status ?? 'trial') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="dashboard_access_status" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
            <option value="active" @selected(($restaurant->dashboard_access_status ?? 'active') === 'active')>Dashboard active</option>
            <option value="payment_required" @selected(($restaurant->dashboard_access_status ?? 'active') === 'payment_required')>Payment required</option>
            <option value="revoked" @selected(($restaurant->dashboard_access_status ?? 'active') === 'revoked')>Revoked</option>
        </select>
        @unless($hasDashboardAccessStatus)
            <p class="text-sm text-red-200 md:col-span-6">Run migrations to enable dashboard access controls.</p>
        @endunless
        <div class="text-sm text-zem-muted md:col-span-2">Current plan: {{ $subscription?->plan_name ?? 'Pro' }} - {{ $subscription?->status ?? 'trial' }}</div>
        <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Save access</button>
        <a href="{{ route('menu.show', [$restaurant->slug, 1]) }}" class="rounded-md border border-zem-border px-4 py-2 text-center">View table 1</a>
    </form>
@endforeach
</div>
@endsection
