@props(['status'])
@php
    $classes = [
        'new' => 'bg-yellow-500/15 text-yellow-200 border-yellow-500/30',
        'preparing' => 'bg-blue-500/15 text-blue-200 border-blue-500/30',
        'served' => 'bg-purple-500/15 text-purple-200 border-purple-500/30',
        'paid' => 'bg-zem-green/15 text-green-200 border-zem-green/30',
        'completed' => 'bg-zem-green/20 text-green-100 border-zem-green/40',
        'cancelled' => 'bg-red-500/15 text-red-200 border-red-500/30',
        'pending' => 'bg-yellow-500/15 text-yellow-200 border-yellow-500/30',
        'acknowledged' => 'bg-blue-500/15 text-blue-200 border-blue-500/30',
        'active' => 'bg-zem-green/15 text-green-200 border-zem-green/30',
        'trial' => 'bg-zem-gold/15 text-yellow-100 border-zem-gold/30',
        'unpaid' => 'bg-red-500/15 text-red-200 border-red-500/30',
        'payment_required' => 'bg-red-500/15 text-red-200 border-red-500/30',
        'revoked' => 'bg-red-950/70 text-red-100 border-red-500/40',
    ][$status] ?? 'bg-zem-border text-zem-muted border-zem-border';
@endphp
<span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $classes }}">{{ str_replace('_', ' ', $status) }}</span>
