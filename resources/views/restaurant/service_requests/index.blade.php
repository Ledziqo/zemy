@extends('layouts.dashboard', ['heading' => 'Service Requests'])

@section('content')
<div class="grid gap-3">
@foreach($requests as $requestRow)
    <form method="post" action="{{ route('restaurant.service-requests.update', $requestRow) }}" class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-zem-border bg-zem-card p-4">@csrf @method('PATCH')<div><strong>Table {{ $requestRow->table_number }} · {{ str_replace('_', ' ', $requestRow->type) }}</strong><p class="text-sm text-zem-muted">{{ $requestRow->created_at->diffForHumans() }} {{ $requestRow->note ? '· '.$requestRow->note : '' }}</p></div><x-status :status="$requestRow->status" /><select name="status" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">@foreach(['pending','acknowledged','completed'] as $status)<option value="{{ $status }}" @selected($requestRow->status===$status)>{{ $status }}</option>@endforeach</select><button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Update</button></form>
@endforeach
</div>
@endsection
