@extends('layouts.dashboard', ['heading' => 'Demo Requests'])

@section('content')
<div class="grid gap-3">@foreach($requests as $demo)<form method="post" action="{{ route('admin.demo-requests.update', $demo) }}" class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-zem-border bg-zem-card p-4">@csrf @method('PATCH')<div><strong>{{ $demo->restaurant_name }}</strong><p class="text-sm text-zem-muted">{{ $demo->name }} · {{ $demo->phone }} · {{ $demo->location }} · {{ $demo->email }}</p><p class="text-sm">{{ $demo->message }}</p></div><x-status :status="$demo->status" /><select name="status" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">@foreach(['new','contacted','converted','closed'] as $status)<option value="{{ $status }}" @selected($demo->status===$status)>{{ $status }}</option>@endforeach</select><button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Update</button></form>@endforeach</div>
@endsection
