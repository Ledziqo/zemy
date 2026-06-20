@extends('layouts.dashboard', ['heading' => 'Demo Requests'])

@section('content')
<div class="grid gap-3">
    @forelse($requests as $demo)
        <form method="post" action="{{ route('admin.demo-requests.update', $demo) }}" class="rounded-md border border-zem-border bg-zem-card p-4">
            @csrf @method('PATCH')
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-display text-lg font-bold">{{ $demo->restaurant_name }}</h3>
                        @php($statusColors = ['new' => 'bg-zem-gold/20 text-zem-gold border-zem-gold/40', 'contacted' => 'bg-blue-100 text-blue-700 border-blue-300', 'converted' => 'bg-green-100 text-green-700 border-green-300', 'closed' => 'bg-gray-100 text-gray-600 border-gray-300'])
                        <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $statusColors[$demo->status] ?? 'border-zem-border text-zem-muted' }}">{{ $demo->status }}</span>
                    </div>
                    <div class="mt-2 grid gap-1 text-sm text-zem-muted">
                        <p><strong class="text-zem-cream">{{ $demo->name }}</strong> - {{ $demo->phone }}</p>
                        @if($demo->email)<p>{{ $demo->email }}</p>@endif
                        @if($demo->location)<p>Location: {{ $demo->location }}</p>@endif
                    </div>
                    @if($demo->message)
                        <p class="mt-2 rounded-md bg-zem-bg p-3 text-sm">{{ $demo->message }}</p>
                    @endif
                </div>
                <div class="flex flex-col gap-2">
                    <select name="status" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2 text-sm">
                        @foreach(['new','contacted','converted','closed'] as $status)
                            <option value="{{ $status }}" @selected($demo->status===$status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-md bg-zem-gold px-4 py-2 text-sm font-bold text-white">Update</button>
                </div>
            </div>
        </form>
    @empty
        <div class="rounded-md border border-zem-border bg-zem-card p-8 text-center">
            <div class="text-3xl mb-2">📭</div>
            <p class="text-zem-muted">No demo requests yet.</p>
        </div>
    @endforelse
</div>
@endsection
