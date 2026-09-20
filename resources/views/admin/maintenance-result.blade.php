@extends('layouts.dashboard', ['heading' => 'Database maintenance', 'eyebrow' => 'Admin Maintenance'])

@section('content')
<div class="max-w-3xl rounded-md border {{ $success ? 'border-emerald-400/40' : 'border-red-400/50' }} bg-zem-card p-5">
    <h2 class="font-display text-xl font-bold">{{ $success ? 'Maintenance completed' : 'Maintenance failed' }}</h2>
    <p class="mt-1 text-sm text-zem-muted">{{ $success ? 'The server finished the requested migration and cache work.' : 'The server returned an error while running maintenance.' }}</p>
    <pre class="mt-4 whitespace-pre-wrap rounded-md border border-zem-border bg-zem-bg p-4 text-sm text-zem-muted">{{ $output }}</pre>
    <a href="/admin/database" class="mt-5 inline-flex rounded-md bg-zem-gold px-5 py-3 font-bold text-white">Back to database maintenance</a>
</div>
@endsection
