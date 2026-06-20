@extends('layouts.dashboard', ['heading' => 'Subscriptions'])

@section('content')
<div class="mb-6 rounded-md border border-zem-border bg-zem-card p-4">
    <h2 class="mb-3 font-display text-lg font-bold">Add subscription</h2>
    <form method="post" action="{{ route('admin.subscriptions.store') }}" class="grid gap-3 md:grid-cols-5">
        @csrf
        <select name="restaurant_id" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
            @foreach($restaurants as $restaurant)
                <option value="{{ $restaurant->id }}">{{ $restaurant->name }}</option>
            @endforeach
        </select>
        <input name="plan_name" required placeholder="Plan name" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <input name="monthly_price" type="number" step="0.01" placeholder="Monthly price" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <select name="status" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
            <option>trial</option>
            <option>active</option>
            <option>unpaid</option>
            <option>cancelled</option>
        </select>
        <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Create</button>
    </form>
</div>

<div class="grid gap-3">
    @forelse($subscriptions as $subscription)
        <form method="post" action="{{ route('admin.subscriptions.update', $subscription) }}" class="rounded-md border border-zem-border bg-zem-card p-4">
            @csrf @method('PATCH')
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-display text-lg font-bold">{{ $subscription->restaurant->name ?? 'Unknown' }}</h3>
                        @php($statusColors = ['active' => 'bg-green-100 text-green-700 border-green-300', 'unpaid' => 'bg-red-100 text-red-700 border-red-300', 'trial' => 'bg-zem-gold/20 text-zem-gold border-zem-gold/40', 'cancelled' => 'bg-gray-100 text-gray-600 border-gray-300'])
                        <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $statusColors[$subscription->status] ?? 'border-zem-border text-zem-muted' }}">{{ $subscription->status }}</span>
                    </div>
                    <p class="mt-1 text-sm text-zem-muted">{{ $subscription->plan_name }} - {{ number_format($subscription->monthly_price) }} ETB/month</p>
                    @if($subscription->starts_at || $subscription->ends_at)
                        <p class="mt-1 text-sm text-zem-muted">{{ optional($subscription->starts_at)->format('M j, Y') }} to {{ optional($subscription->ends_at)->format('M j, Y') }}</p>
                    @endif
                </div>
            </div>
            <details class="mt-3 rounded-md border border-zem-border bg-zem-bg p-3">
                <summary class="cursor-pointer text-sm font-bold text-zem-cream">Edit subscription</summary>
                <div class="mt-3 grid gap-3 md:grid-cols-6">
                    <select name="restaurant_id" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                        @foreach($restaurants as $restaurant)
                            <option value="{{ $restaurant->id }}" @selected($subscription->restaurant_id===$restaurant->id)>{{ $restaurant->name }}</option>
                        @endforeach
                    </select>
                    <input name="plan_name" value="{{ $subscription->plan_name }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <input name="monthly_price" value="{{ $subscription->monthly_price }}" type="number" step="0.01" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <select name="status" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                        @foreach(['active','unpaid','trial','cancelled'] as $status)
                            <option value="{{ $status }}" @selected($subscription->status===$status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    <input name="starts_at" type="date" value="{{ optional($subscription->starts_at)->format('Y-m-d') }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <input name="ends_at" type="date" value="{{ optional($subscription->ends_at)->format('Y-m-d') }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white md:col-span-6">Save changes</button>
                </div>
            </details>
        </form>
    @empty
        <div class="rounded-md border border-zem-border bg-zem-card p-8 text-center">
            <div class="text-3xl mb-2">💳</div>
            <p class="text-zem-muted">No subscriptions yet. Create one above.</p>
        </div>
    @endforelse
</div>
@endsection
