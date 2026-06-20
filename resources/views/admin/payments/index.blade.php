@extends('layouts.dashboard', ['heading' => 'Payments', 'eyebrow' => 'Subscription Tracking'])

@section('content')
<div class="grid gap-4 md:grid-cols-3 xl:grid-cols-5">
    @foreach([['Total accounts',$summary['total']],['Active',$summary['active']],['Expiring soon',$summary['expiring']],['Expired',$summary['expired']],['Unpaid',$summary['unpaid']]] as $card)
        <div class="rounded-md border border-zem-border bg-zem-card p-4"><p class="text-sm text-zem-muted">{{ $card[0] }}</p><p class="mt-2 text-2xl font-extrabold">{{ $card[1] }}</p></div>
    @endforeach
</div>

<div class="mt-6 grid gap-3">
    @foreach($rows as $row)
        <div class="rounded-md border border-zem-border bg-zem-card p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-display text-lg font-bold">{{ $row->restaurant->name }}</h3>
                        <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $row->statusColor }}">{{ $row->statusLabel }}</span>
                    </div>
                    @if($row->subscription)
                        <div class="mt-2 grid gap-1 text-sm text-zem-muted">
                            <p>Plan: <strong class="text-zem-cream">{{ $row->subscription->plan_name }}</strong> - {{ number_format($row->subscription->monthly_price) }} ETB/month</p>
                            <p>Started: {{ $row->subscription->starts_at?->format('M j, Y') ?? 'N/A' }} | Expires: {{ $row->subscription->ends_at?->format('M j, Y') ?? 'N/A' }}</p>
                            @if($row->subscription->payment_method)
                                <p>Paid via: <strong class="text-zem-cream">{{ $row->subscription->payment_method }}</strong></p>
                            @endif
                        </div>
                    @else
                        <p class="mt-2 text-sm text-zem-muted">No subscription set up yet.</p>
                    @endif
                </div>
                @if($row->subscription)
                    <details class="rounded-md border border-zem-border bg-zem-bg p-3">
                        <summary class="cursor-pointer text-sm font-bold text-zem-cream">Mark as paid</summary>
                        <form method="post" action="{{ route('admin.payments.mark-paid', $row->subscription) }}" class="mt-3 grid gap-2">
                            @csrf
                            <select name="payment_method" class="rounded-md border border-zem-border bg-zem-card px-3 py-2 text-sm">
                                <option value="">Payment method</option>
                                <option value="telebirr">Telebirr</option>
                                <option value="cbe">CBE</option>
                                <option value="awash">Awash Bank</option>
                                <option value="abyssinia">Bank of Abyssinia</option>
                                <option value="cash">Cash</option>
                            </select>
                            <select name="extend_days" class="rounded-md border border-zem-border bg-zem-card px-3 py-2 text-sm">
                                <option value="30">Extend 30 days</option>
                                <option value="7">Extend 7 days</option>
                                <option value="1">Extend 1 day</option>
                            </select>
                            <button class="rounded-md bg-zem-gold px-4 py-2 text-sm font-bold text-white">Confirm payment</button>
                        </form>
                    </details>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection
