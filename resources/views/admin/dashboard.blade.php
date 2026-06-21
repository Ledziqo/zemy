@extends('layouts.dashboard', ['heading' => 'ZemTab Admin', 'eyebrow' => 'SaaS Overview'])

@section('content')
<div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
    @foreach([['Accounts',$totalRestaurants],['Active',$activeRestaurants],['Customer orders',$totalOrders],['Demo requests',$pendingDemoRequests],['Paid subs',$activeSubscriptions],['Unpaid',$unpaidSubscriptions]] as $card)
        <div class="rounded-md border border-zem-border bg-zem-card p-4"><p class="text-sm text-zem-muted">{{ $card[0] }}</p><p class="mt-2 text-2xl font-extrabold">{{ $card[1] }}</p></div>
    @endforeach
</div>

<div class="mt-6 rounded-md border border-zem-border bg-zem-card p-4">
    <h2 class="font-display text-xl font-bold">Database maintenance</h2>
    <p class="mt-1 text-sm text-zem-muted">Run migrations, seed demo data, and clear all caches. Use this after deploying code updates. Enter your database password to confirm.</p>
    
    <form method="post" action="{{ route('admin.setup.run') }}" class="mt-4">
        @csrf
        <div class="grid gap-3 md:grid-cols-2">
            <label class="grid gap-1 text-sm">
                <span class="font-bold text-zem-muted">DB Host</span>
                <input name="db_host" value="{{ old('db_host', config('database.connections.mysql.host')) }}" placeholder="localhost" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-bold text-zem-muted">DB Name</span>
                <input name="db_database" value="{{ old('db_database', config('database.connections.mysql.database')) }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-bold text-zem-muted">DB Username</span>
                <input name="db_username" value="{{ old('db_username', config('database.connections.mysql.username')) }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-bold text-zem-muted">DB Password</span>
                <input name="db_password" type="password" placeholder="Enter database password" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
            </label>
        </div>
        <button class="mt-4 rounded-md bg-zem-gold px-5 py-3 font-bold text-white">Run setup now</button>
    </form>
    @if(session('setup_output'))
        <div class="mt-4 rounded-md border border-zem-border bg-zem-bg p-4">
            <pre class="whitespace-pre-wrap text-sm text-zem-muted">{{ session('setup_output') }}</pre>
        </div>
    @endif
</div>

<section class="mt-6 rounded-md border border-zem-border bg-zem-card p-4">
    <div class="flex items-center justify-between"><h2 class="font-display text-xl font-bold">Restaurants & Hotels</h2><a class="text-sm font-bold text-zem-gold" href="{{ route('admin.restaurants.index') }}">Manage all</a></div>
    <div class="mt-4 grid gap-3 md:grid-cols-2">
        @foreach($restaurants as $restaurant)
            @php($subscription = $hasSubscriptions ? $restaurant->subscriptions->sortByDesc('created_at')->first() : null)
            <div class="rounded-md border border-zem-border bg-zem-bg p-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <strong>{{ $restaurant->name }}</strong>
                    <span class="rounded-full border border-zem-border bg-zem-soft px-3 py-1 text-xs font-bold text-zem-cream">{{ $restaurant->businessTypeLabel() }}</span>
                    <span class="rounded-full border border-zem-border bg-zem-soft px-3 py-1 text-xs font-bold text-zem-cream">{{ number_format($restaurant->orders_count) }} orders</span>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-zem-muted">
                    <span>{{ $restaurant->location ?: 'No location' }}</span>
                    <span>-</span>
                    <span>{{ $subscription?->status ?? 'no subscription' }}</span>
                    <x-status :status="$hasDashboardAccessStatus ? ($restaurant->dashboard_access_status ?? 'active') : 'active'" />
                </div>
            </div>
        @endforeach
    </div>
</section>
@endsection
