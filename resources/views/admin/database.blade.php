@extends('layouts.dashboard', ['heading' => 'Database', 'eyebrow' => 'Admin Maintenance'])

@section('content')
<div class="max-w-3xl rounded-md border border-zem-border bg-zem-card p-5">
    <h2 class="font-display text-xl font-bold">Database maintenance</h2>
    <p class="mt-1 text-sm text-zem-muted">Run migrations, seed demo data, and clear all caches after deploying code updates. Enter the database password to confirm.</p>

    <form method="post" action="{{ route('admin.setup.run') }}" class="mt-5">
        @csrf
        <div class="grid gap-3 md:grid-cols-2">
            <label class="grid gap-1 text-sm">
                <span class="font-bold text-zem-muted">DB Host</span>
                <input name="db_host" value="{{ old('db_host', config('database.connections.mysql.host')) }}" placeholder="localhost" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-bold text-zem-muted">DB Name</span>
                <input name="db_database" value="{{ old('db_database', config('database.connections.mysql.database')) }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-bold text-zem-muted">DB Username</span>
                <input name="db_username" value="{{ old('db_username', config('database.connections.mysql.username')) }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-bold text-zem-muted">DB Password</span>
                <input name="db_password" type="password" placeholder="Enter database password" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
            </label>
        </div>
        <button class="mt-4 rounded-md bg-zem-gold px-5 py-3 font-bold text-white">Run database maintenance</button>
    </form>

    @if(session('setup_output'))
        <div class="mt-4 rounded-md border border-zem-border bg-zem-bg p-4">
            <pre class="whitespace-pre-wrap text-sm text-zem-muted">{{ session('setup_output') }}</pre>
        </div>
    @endif
</div>
@endsection
