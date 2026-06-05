@extends('layouts.dashboard', ['heading' => 'ZemTab Admin', 'eyebrow' => 'SaaS Overview'])

@section('content')
<div class="grid gap-4 md:grid-cols-5">
    @foreach([['Accounts',$totalRestaurants],['Active',$activeRestaurants],['Customer orders',$totalOrders],['Demo requests',$pendingDemoRequests],['Paid subs',$activeSubscriptions],['Unpaid',$unpaidSubscriptions],['Revoked',$revokedRestaurants]] as $card)
        <div class="rounded-md border border-zem-border bg-zem-card p-4"><p class="text-sm text-zem-muted">{{ $card[0] }}</p><p class="mt-2 text-2xl font-extrabold">{{ $card[1] }}</p></div>
    @endforeach
</div>
<section class="mt-6 rounded-md border border-zem-border bg-zem-card p-4">
    <div class="flex items-center justify-between"><h2 class="font-display text-xl font-bold">Restaurants & Hotels</h2><a class="text-sm font-bold text-zem-gold" href="{{ route('admin.restaurants.index') }}">Manage all</a></div>
    <div class="mt-4 grid gap-3 md:grid-cols-2">
        @foreach($restaurants as $restaurant)
            @php($subscription = $hasSubscriptions ? $restaurant->subscriptions->sortByDesc('created_at')->first() : null)
            <div class="rounded-md border border-zem-border bg-zem-bg p-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <strong>{{ $restaurant->name }}</strong>
                    <span class="rounded-full border border-zem-border bg-black px-3 py-1 text-xs font-bold text-zem-muted">{{ $restaurant->businessTypeLabel() }}</span>
                    <span class="rounded-full border border-zem-border bg-black px-3 py-1 text-xs font-bold text-zem-muted">{{ number_format($restaurant->orders_count) }} orders</span>
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
