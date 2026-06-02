@extends('layouts.dashboard', ['heading' => 'Orders Overview'])

@section('content')
<div class="grid gap-3">@foreach($orders as $order)<article class="rounded-md border border-zem-border bg-zem-card p-4"><div class="flex flex-wrap justify-between gap-3"><div><strong>#{{ $order->id }} · {{ $order->restaurant->name }}</strong><p class="text-sm text-zem-muted">Table {{ $order->table_number }} · {{ $order->created_at->diffForHumans() }}</p></div><x-status :status="$order->status" /></div><p class="mt-2 text-sm text-zem-muted">{{ $order->items->map(fn($i) => $i->quantity.'x '.$i->item_name)->join(', ') }}</p><p class="mt-2 font-bold">{{ number_format($order->total) }} ETB</p></article>@endforeach</div>
@endsection
