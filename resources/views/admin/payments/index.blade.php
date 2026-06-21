@extends('layouts.dashboard', ['heading' => 'Payments', 'eyebrow' => 'Subscription Tracking'])

@section('content')
<div class="grid gap-4 md:grid-cols-3 xl:grid-cols-5">
    @foreach([['Total accounts',$summary['total']],['Active',$summary['active']],['Expiring soon',$summary['expiring']],['Expired',$summary['expired']],['Unpaid',$summary['unpaid']]] as $card)
        <div class="rounded-md border border-zem-border bg-zem-card p-4"><p class="text-sm text-zem-muted">{{ $card[0] }}</p><p class="mt-2 text-2xl font-extrabold">{{ $card[1] }}</p></div>
    @endforeach
</div>

@php($expiringRows = $rows->filter(fn($r) => $r->daysLeft !== null && $r->daysLeft >= 0 && $r->daysLeft <= 3))
        @if($expiringRows->isNotEmpty())
        <div class="mt-6 rounded-md border border-zem-gold/40 bg-zem-gold/10 p-4">
            <h2 class="font-display text-lg font-bold text-zem-gold">⚠️ Expiring Soon (≤ 3 days)</h2>
            <div class="mt-3 grid gap-2">
                @foreach($expiringRows as $row)
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-md bg-white p-3 text-sm">
                        <div>
                            <strong>{{ $row->restaurant->name }}</strong>
                            <span class="ml-2 text-zem-muted">{{ $row->daysLeft }} day(s) left</span>
                            @if($row->restaurant->phone)
                                <a href="tel:{{ $row->restaurant->phone }}" class="ml-3 inline-flex items-center gap-1 rounded-full border border-zem-gold/40 px-3 py-1 text-xs font-bold text-zem-gold hover:bg-zem-gold hover:text-white">📞 {{ $row->restaurant->phone }}</a>
                            @endif
                        </div>
                        @if($row->subscription)
                            <a href="#sub-{{ $row->subscription->id }}" class="text-xs font-bold text-zem-gold" onclick="document.getElementById('sub-{{ $row->subscription->id }}').open=true">Manage →</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif

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
                    <details id="sub-{{ $row->subscription->id }}" class="rounded-md border border-zem-border bg-zem-bg p-3">
                        <summary class="cursor-pointer text-sm font-bold text-zem-cream">Manage subscription</summary>
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
                            <div class="grid grid-cols-2 gap-1">
                                <button type="button" onclick="this.parentElement.nextElementSibling.value=30; this.form.submit()" class="rounded-md border border-zem-border px-2 py-1 text-xs font-bold">+30 days</button>
                                <button type="button" onclick="this.parentElement.nextElementSibling.value=7; this.form.submit()" class="rounded-md border border-zem-border px-2 py-1 text-xs font-bold">+7 days</button>
                                <button type="button" onclick="this.parentElement.nextElementSibling.value=1; this.form.submit()" class="rounded-md border border-zem-border px-2 py-1 text-xs font-bold">+1 day</button>
                                <button type="button" onclick="this.parentElement.nextElementSibling.value=-1; this.form.submit()" class="rounded-md border border-zem-border px-2 py-1 text-xs font-bold">-1 day</button>
                                <button type="button" onclick="this.parentElement.nextElementSibling.value=-7; this.form.submit()" class="rounded-md border border-zem-border px-2 py-1 text-xs font-bold">-7 days</button>
                                <button type="button" onclick="this.parentElement.nextElementSibling.value=-30; this.form.submit()" class="rounded-md border border-zem-border px-2 py-1 text-xs font-bold">-30 days</button>
                            </div>
                            <input type="number" name="extend_days" placeholder="Custom days (+/-)" class="rounded-md border border-zem-border bg-zem-card px-3 py-2 text-sm" value="30">
                            <label class="text-xs font-bold text-zem-muted mt-1">Or set exact end date:</label>
                            <input type="date" name="custom_ends_at" class="rounded-md border border-zem-border bg-zem-card px-3 py-2 text-sm">
                            <button class="rounded-md bg-zem-gold px-4 py-2 text-sm font-bold text-white mt-1">Save changes</button>
                        </form>
                    </details>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection
