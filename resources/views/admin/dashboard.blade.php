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
                    <div class="flex items-center gap-3"><span class="text-xs text-zem-muted">{{ $subscription ? ucfirst($subscription->effectiveStatus()) : 'No subscription' }}</span><x-status :status="!$restaurant->is_active ? 'inactive' : ($hasDashboardAccessStatus ? ($restaurant->dashboard_access_status ?? 'active') : 'active')" /></div>
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
        <section class="border border-zem-border bg-zem-card p-5">
            <div class="flex items-start justify-between gap-3"><div><h2 class="font-semibold">Billing watch</h2><p class="mt-1 text-xs text-zem-muted">Upcoming renewals and accounts needing attention.</p></div><a href="{{ route('admin.payments.index') }}" class="text-xs font-semibold text-zem-gold">All billing →</a></div>
            <div class="mt-4 grid grid-cols-3 gap-2">
                <div class="rounded-xl bg-zem-soft p-3"><p class="text-[11px] text-zem-muted">Due soon</p><p class="mt-1 text-lg font-semibold">{{ number_format($billingDueSoon) }}</p></div>
                <div class="rounded-xl bg-red-500/10 p-3"><p class="text-[11px] text-red-300">Overdue</p><p class="mt-1 text-lg font-semibold text-red-200">{{ number_format($billingOverdue) }}</p></div>
                <div class="rounded-xl bg-zem-soft p-3"><p class="text-[11px] text-zem-muted">No plan</p><p class="mt-1 text-lg font-semibold">{{ number_format($billingMissing) }}</p></div>
            </div>
            <div class="mt-4 space-y-2">
                @forelse($billingAlerts->take(4) as $billing)
                    <a href="{{ route('admin.payments.index') }}" class="flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 hover:bg-zem-soft">
                        <div class="min-w-0"><p class="truncate text-sm font-semibold">{{ $billing->restaurant->name }}</p><p class="text-xs text-zem-muted">{{ $billing->subscription?->ends_at?->format('M j, Y') ?? 'Subscription setup needed' }}</p></div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ in_array($billing->attention, ['overdue', 'missing'], true) ? 'bg-red-500/10 text-red-300' : 'bg-zem-gold/10 text-zem-gold' }}">{{ $billing->label }}</span>
                    </a>
                @empty
                    <p class="rounded-xl bg-zem-soft px-3 py-3 text-sm text-zem-muted">No billing alerts. Everything is up to date.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
