@extends('layouts.dashboard', ['heading' => 'Payments', 'eyebrow' => 'Subscription Tracking'])

@section('content')
<p class="mb-5 text-sm text-zem-muted">Monthly payments are due on the 1st. The account stays open with a warning on the 1st–7th and locks on the 8th if unpaid. Verify a payment below to renew to the next calendar month.</p>
<div class="grid gap-4 md:grid-cols-3 xl:grid-cols-5">
    @foreach([['Total accounts',$summary['total']],['Active',$summary['active']],['Expiring soon',$summary['expiring']],['Suspended',$summary['expired']],['Unpaid',$summary['unpaid']]] as $card)
        <div class="rounded-md border border-zem-border bg-zem-card p-4"><p class="text-sm text-zem-muted">{{ $card[0] }}</p><p class="mt-2 text-2xl font-extrabold">{{ $card[1] }}</p></div>
    @endforeach
</div>

@php($expiringRows = $rows->filter(fn($r) => $r->daysLeft !== null && $r->daysLeft >= -(\App\Models\Subscription::PAYMENT_GRACE_DAYS - 1) && $r->daysLeft <= 7))
        @if($expiringRows->isNotEmpty())
        <div class="mt-6 rounded-md border border-zem-gold/40 bg-zem-gold/10 p-4">
            <h2 class="font-display text-lg font-bold text-zem-gold">Payments due or coming up</h2>
            <div class="mt-3 grid gap-2">
                @foreach($expiringRows as $row)
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-md bg-white p-3 text-sm">
                        <div>
                            <strong>{{ $row->restaurant->name }}</strong>
                            <span class="ml-2 text-zem-muted">{{ $row->subscription->isInPaymentGrace() ? 'Payment due — grace through '.$row->subscription->graceEndsAt()->format('M j') : $row->daysLeft.' day(s) until payment' }}</span>
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
                            <p>Started: {{ $row->subscription->starts_at?->format('M j, Y') ?? 'N/A' }} | Next payment: {{ $row->subscription->paymentDueAt()?->format('M j, Y') ?? 'N/A' }}</p>
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
                            <label class="text-xs font-bold text-zem-muted">Renewal period</label>
                            <select name="renew_months" class="rounded-md border border-zem-border bg-zem-card px-3 py-2 text-sm"><option value="1">1 calendar month</option><option value="3">3 calendar months</option><option value="6">6 calendar months</option><option value="12">12 calendar months</option></select>
                            <label class="text-xs font-bold text-zem-muted mt-1">Or choose the next payment due date:</label>
                            <input type="date" name="custom_ends_at" aria-label="Next payment due" class="rounded-md border border-zem-border bg-zem-card px-3 py-2 text-sm">
                            <p class="text-xs text-zem-muted">Non-first dates move forward to the next 1st. Suspended months are not added to this renewal.</p>
                            <button class="rounded-md bg-zem-gold px-4 py-2 text-sm font-bold text-white mt-1">Verify payment &amp; renew</button>
                        </form>
                    </details>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection
