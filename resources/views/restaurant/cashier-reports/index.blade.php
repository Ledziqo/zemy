@extends('layouts.dashboard', ['heading' => 'Cashier Reports', 'eyebrow' => 'Revenue per cashier & payment breakdown'])

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-6 flex flex-wrap items-end gap-3">
        <form method="get" class="flex flex-wrap gap-3">
            <label class="grid gap-1 text-sm">
                <span class="font-bold">From</span>
                <input type="date" name="date_from" value="{{ $date_from }}" class="rounded-lg border border-zem-border bg-white px-3 py-2">
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-bold">To</span>
                <input type="date" name="date_to" value="{{ $date_to }}" class="rounded-lg border border-zem-border bg-white px-3 py-2">
            </label>
            <button class="rounded-lg bg-zem-gold px-4 py-2.5 text-sm font-bold text-white">Filter</button>
        </form>
        <button onclick="window.print()" class="rounded-lg border border-zem-border px-4 py-2.5 text-sm font-bold text-zem-muted">Export PDF</button>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-lg border border-zem-border bg-zem-card p-5">
            <p class="text-xs text-zem-muted">Total Revenue</p>
            <p class="mt-1 text-3xl font-extrabold">{{ number_format($grand_total) }} ETB</p>
        </div>
        <div class="rounded-lg border border-zem-border bg-zem-card p-5">
            <p class="text-xs text-zem-muted">Total Orders Handled</p>
            <p class="mt-1 text-3xl font-extrabold">{{ $grand_orders }}</p>
        </div>
    </div>

    <div class="mb-6 rounded-lg border border-zem-border bg-zem-card p-5">
        <h2 class="mb-3 font-bold">Payment Method Breakdown</h2>
        <div class="grid gap-3 sm:grid-cols-5">
            @foreach($payment_methods as $method)
                <div class="rounded-md border border-zem-border bg-zem-bg p-3 text-center">
                    <p class="text-xs capitalize text-zem-muted">{{ $method }}</p>
                    <p class="mt-1 text-lg font-bold">{{ number_format($payment_method_totals[$method]) }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="space-y-4">
        @foreach($cashiers as $row)
            <div class="rounded-lg border border-zem-border bg-zem-card p-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">
                            {{ strtoupper(substr($row['cashier']->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-bold">{{ $row['cashier']->name }}</p>
                            <p class="text-xs text-zem-muted">{{ $row['order_count'] }} orders</p>
                        </div>
                    </div>
                    <p class="text-xl font-extrabold">{{ number_format($row['total_revenue']) }} ETB</p>
                </div>
                <div class="mt-3 grid gap-2 sm:grid-cols-5">
                    @foreach($payment_methods as $method)
                        @if($row['method_breakdown'][$method] > 0)
                            <div class="rounded-md bg-zem-bg px-3 py-2 text-center">
                                <p class="text-xs capitalize text-zem-muted">{{ $method }}</p>
                                <p class="text-sm font-bold">{{ number_format($row['method_breakdown'][$method]) }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
