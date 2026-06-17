@props(['status'])
@php
    $classes = [
        'new' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
        'preparing' => 'bg-blue-100 text-blue-800 border-blue-300',
        'served' => 'bg-purple-100 text-purple-800 border-purple-300',
        'paid' => 'bg-green-100 text-green-800 border-green-300',
        'completed' => 'bg-green-100 text-green-800 border-green-300',
        'cancelled' => 'bg-red-100 text-red-800 border-red-300',
        'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
        'acknowledged' => 'bg-blue-100 text-blue-800 border-blue-300',
        'active' => 'bg-green-100 text-green-800 border-green-300',
        'trial' => 'bg-zem-gold/10 text-zem-redDark border-zem-gold/30',
        'unpaid' => 'bg-red-100 text-red-800 border-red-300',
        'payment_required' => 'bg-red-100 text-red-800 border-red-300',
        'revoked' => 'bg-red-950/70 text-red-100 border-red-500/40',
    ][$status] ?? 'bg-zem-border text-zem-muted border-zem-border';
@endphp
<span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $classes }}">{{ str_replace('_', ' ', $status) }}</span>
