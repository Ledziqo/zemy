@extends('layouts.dashboard', ['heading' => 'ZemTab Admin', 'eyebrow' => 'SaaS Overview'])

@section('content')
<div class="grid gap-4 md:grid-cols-5">
    @foreach([['Restaurants',$totalRestaurants],['Active',$activeRestaurants],['Orders',$totalOrders],['Demo requests',$pendingDemoRequests],['Paid subs',$activeSubscriptions],['Unpaid',$unpaidSubscriptions],['Revoked',$revokedRestaurants]] as $card)
        <div class="rounded-md border border-zem-border bg-zem-card p-4"><p class="text-sm text-zem-muted">{{ $card[0] }}</p><p class="mt-2 text-2xl font-extrabold">{{ $card[1] }}</p></div>
    @endforeach
</div>
<section class="mt-6 rounded-md border border-zem-border bg-zem-card p-4">
    <div class="flex items-center justify-between"><h2 class="font-display text-xl font-bold">Restaurants</h2><a class="text-sm font-bold text-zem-gold" href="{{ route('admin.restaurants.index') }}">Manage all</a></div>
    <div class="mt-4 grid gap-3 md:grid-cols-2">
        @foreach($restaurants as $restaurant)
            @php($subscription = $hasSubscriptions ? $restaurant->subscriptions->sortByDesc('created_at')->first() : null)
            <div class="rounded-md border border-zem-border bg-zem-bg p-3">
                <div class="flex flex-wrap items-center justify-between gap-2"><strong>{{ $restaurant->name }}</strong><x-status :status="$hasDashboardAccessStatus ? ($restaurant->dashboard_access_status ?? 'active') : 'active'" /></div>
                <p class="mt-1 text-sm text-zem-muted">{{ $restaurant->location ?: 'No location' }} - {{ $subscription?->status ?? 'no subscription' }}</p>
            </div>
        @endforeach
    </div>
</section>
<section class="mt-6 rounded-md border border-zem-border bg-zem-card p-4">
    <h2 class="font-display text-xl font-bold">Recent orders</h2>
    <div class="mt-4 grid gap-3">@foreach($recentOrders as $order)<div class="rounded-md border border-zem-border bg-zem-bg p-3"><strong>#{{ $order->id }} · {{ $order->restaurant->name }}</strong><p class="text-sm text-zem-muted">Table {{ $order->table_number }} · {{ number_format($order->total) }} ETB</p></div>@endforeach</div>
</section>
@endsection
