@extends('layouts.dashboard', ['heading' => 'Service Requests', 'eyebrow' => 'Live '.$restaurant->locationLabel().' requests', 'autoRefreshSeconds' => 5])

@section('content')
@php($placeTitle = $restaurant->locationLabelTitle())
<div class="mb-4 grid gap-3 md:grid-cols-3">
    <div class="rounded-md border border-zem-border bg-zem-card p-4">
        <p class="text-sm text-zem-muted">Active requests</p>
        <p class="mt-2 text-3xl font-extrabold">{{ $activeRequests }}</p>
    </div>
    <div class="rounded-md border border-zem-border bg-zem-card p-4 md:col-span-2">
        <p class="text-sm text-zem-muted">Storage note</p>
        <p class="mt-2 text-sm">Requests are tiny, so keep history for now. This page puts pending and acknowledged requests first.</p>
    </div>
</div>

<div class="grid gap-3">
@forelse($requests as $requestRow)
    <form method="post" action="{{ route('restaurant.service-requests.update', $requestRow) }}" class="grid gap-3 rounded-md border border-zem-border bg-zem-card p-4 md:grid-cols-[1fr_auto_auto_auto] md:items-center">
        @csrf @method('PATCH')
        <div>
            <strong>{{ $placeTitle }} {{ $requestRow->table_number }} - {{ $restaurant->requestTypeLabel($requestRow->type) }}</strong>
            <p class="text-sm text-zem-muted">{{ $requestRow->created_at->diffForHumans() }} {{ $requestRow->note ? '- '.$requestRow->note : '' }}</p>
        </div>
        <x-status :status="$requestRow->status" />
        <select name="status" class="rounded-md border border-zem-border bg-zem-bg px-3 py-3">
            @foreach(['pending','acknowledged','completed'] as $status)
                <option value="{{ $status }}" @selected($requestRow->status===$status)>{{ $status }}</option>
            @endforeach
        </select>
        <button class="rounded-md bg-zem-gold px-4 py-3 font-bold text-white">Update</button>
    </form>
@empty
    <p class="rounded-md border border-zem-border bg-zem-card p-4 text-zem-muted">No service requests yet.</p>
@endforelse
</div>

<div class="mt-5">{{ $requests->links() }}</div>
@endsection
