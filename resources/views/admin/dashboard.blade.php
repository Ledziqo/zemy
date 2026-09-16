@extends('layouts.dashboard', ['heading' => 'Overview', 'eyebrow' => 'ZemTab administration'])

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4"><div><h2 class="text-lg font-semibold">A clear view of your platform</h2><p class="mt-1 text-sm text-zem-muted">Manage accounts, follow up on requests and keep billing on track.</p></div><a href="{{ route('admin.restaurants.index') }}" class="rounded-xl bg-zem-gold px-5 py-3 text-sm font-semibold text-white hover:bg-zem-redDark">Manage accounts →</a></div>
<div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
    @foreach([['Accounts', number_format($totalRestaurants), number_format($activeRestaurants).' active'], ['Active subscriptions', number_format($activeSubscriberCount), 'Current paid plans'], ['Monthly recurring revenue', number_format($monthlyRevenue, 2).' ETB', 'Current active plans'], ['Customer orders', number_format($totalOrders), 'Across all accounts']] as [$label, $value, $description])
        <div class="rounded-2xl border border-zem-border bg-zem-card p-4 md:p-5"><p class="text-sm text-zem-muted">{{ $label }}</p><p class="metric-value mt-3 break-words text-2xl font-semibold">{{ $value }}</p><p class="mt-2 text-xs text-zem-muted">{{ $description }}</p></div>
    @endforeach
</div>
<div class="mt-6 grid items-start gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(260px,1fr)]">
    <section class="overflow-hidden border border-zem-border bg-zem-card">
        <div class="flex items-center justify-between gap-3 border-b border-zem-border px-5 py-4"><h2 class="font-semibold">Recent accounts</h2><a class="text-sm font-semibold text-zem-gold" href="{{ route('admin.restaurants.index') }}">View all →</a></div>
        <div class="divide-y divide-zem-border">
            @forelse($restaurants as $restaurant)
                @php($subscription = $hasSubscriptions ? $restaurant->subscriptions->sortByDesc('created_at')->first() : null)
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                    <div class="min-w-0"><p class="text-sm font-semibold">{{ $restaurant->name }}</p><p class="mt-1 text-xs text-zem-muted">{{ $restaurant->businessTypeLabel() }} · {{ $restaurant->location ?: 'Location not added' }}</p></div>
                    <div class="flex items-center gap-3"><span class="text-xs text-zem-muted">{{ $subscription ? ucfirst($subscription->status) : 'No subscription' }}</span><x-status :status="!$restaurant->is_active ? 'inactive' : ($hasDashboardAccessStatus ? ($restaurant->dashboard_access_status ?? 'active') : 'active')" /></div>
                </div>
            @empty
                <div class="px-6 py-12 text-center"><h3 class="font-semibold">Your first account starts here</h3><p class="mt-2 text-sm text-zem-muted">Add a restaurant or hotel to set up its menu, staff and QR codes.</p><a href="{{ route('admin.restaurants.index') }}" class="mt-4 inline-block text-sm font-semibold text-zem-gold">Add an account →</a></div>
            @endforelse
        </div>
    </section>
    <div class="space-y-6">
        <section class="border border-zem-border bg-zem-card p-5"><h2 class="font-semibold">Follow-ups</h2><p class="mt-1 text-xs text-zem-muted">The next things to review.</p>
            @foreach([['New demo requests', $pendingDemoRequests, 'admin.demo-requests.index'], ['Unpaid subscriptions', $unpaidSubscriptions, 'admin.subscriptions.index'], ['Revoked accounts', $revokedRestaurants, 'admin.restaurants.index']] as [$label, $count, $destination])
                <a href="{{ route($destination) }}" class="mt-3 flex items-center justify-between gap-3 rounded-xl px-3 py-3 hover:bg-zem-soft"><span class="text-sm">{{ $label }}</span><span class="rounded-lg px-2.5 py-1 text-sm font-semibold {{ $count ? 'bg-zem-gold/10 text-zem-gold' : 'bg-zem-soft text-zem-muted' }}">{{ number_format($count) }}</span></a>
            @endforeach
        </section>
        <section class="border border-zem-border bg-zem-card p-5"><h2 class="font-semibold">Billing</h2><p class="mt-2 text-sm leading-relaxed text-zem-muted">Review incoming payments and subscription details.</p><a href="{{ route('admin.payments.index') }}" class="mt-4 inline-block text-sm font-semibold text-zem-gold">View payments →</a></section>
    </div>
</div>
@endsection
