@extends('layouts.dashboard', ['heading' => 'Database', 'eyebrow' => 'Admin Maintenance'])

@section('content')
<div class="max-w-3xl rounded-md border border-zem-border bg-zem-card p-5">
    <h2 class="font-display text-xl font-bold">Database maintenance</h2>
    <p class="mt-1 text-sm text-zem-muted">Run migrations and clear caches after deploying code updates. Database credentials are only needed when they have changed.</p>

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
        <label class="mt-4 flex items-start gap-2 text-sm text-zem-muted">
            <input type="checkbox" name="seed_demo_data" value="1" class="mt-1 rounded border-zem-border">
            <span>Also refresh demo/admin seed data (normally leave this unchecked in production).</span>
        </label>
        <div class="mt-4 flex flex-wrap gap-3">
            <button name="maintenance" value="1" class="rounded-md bg-zem-gold px-5 py-3 font-bold text-white">Run database maintenance</button>
            <button formaction="{{ route('admin.database.menu-refresh') }}" class="rounded-md border border-emerald-400/50 bg-emerald-400/10 px-5 py-3 font-bold text-emerald-200">Apply Tulip Olympia menu update</button>
        </div>
    </form>

    <p class="mt-3 text-xs text-zem-muted">Use the green button after the latest code and menu images are deployed. It applies the pending menu migration and clears caches; it does not seed demo data.</p>

    @if(session('setup_output'))
        <div class="mt-4 rounded-md border border-zem-border bg-zem-bg p-4">
            <pre class="whitespace-pre-wrap text-sm text-zem-muted">{{ session('setup_output') }}</pre>
        </div>
    @endif
</div>

<div class="mt-6 max-w-3xl rounded-md border border-zem-border bg-zem-card p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="font-display text-xl font-bold">Production stress test</h2>
            <p class="mt-1 text-sm text-zem-muted">Add disposable restaurants in small batches, run the staged browser simulation, then remove only the stress data.</p>
        </div>
        <span class="rounded-full border border-amber-400/40 bg-amber-400/10 px-3 py-1 text-xs font-bold text-amber-200">Temporary data only</span>
    </div>

    @if(! $stressTestEnabled)
        <div class="mt-4 rounded-md border border-amber-400/40 bg-amber-400/10 p-4 text-sm text-amber-100">
            Production stress controls are locked until you enable temporary stress mode.
            <form method="post" action="{{ route('admin.setup.run') }}" class="mt-3">
                @csrf
                <input type="hidden" name="stress_mode" value="enable">
                <button class="rounded-md bg-amber-500 px-4 py-2 text-sm font-bold text-black hover:bg-amber-400">Enable temporary stress mode</button>
            </form>
        </div>
    @else
        <p class="mt-4 text-sm text-zem-muted">Create one batch at a time. The simulator polls each restaurant's Work Board like an open screen while guests scan menus and place test orders. Maximum: {{ $stressMaxRestaurants }} restaurants.</p>
        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-5">
            @for($batch = 1; $batch <= intdiv($stressMaxRestaurants + $stressBatchSize - 1, $stressBatchSize); $batch++)
                @php($start = (($batch - 1) * $stressBatchSize) + 1)
                @php($end = min($batch * $stressBatchSize, $stressMaxRestaurants))
                <form method="post" action="{{ route('admin.setup.run') }}">
                    @csrf
                    <input type="hidden" name="seed_stress_data" value="1">
                    <input type="hidden" name="stress_batch" value="{{ $batch }}">
                    <button class="w-full rounded-md border border-zem-border bg-zem-bg px-3 py-2 text-sm font-bold hover:border-zem-gold">Add {{ $start }}–{{ $end }}</button>
                </form>
            @endfor
        </div>
        <form method="post" action="{{ route('admin.setup.run') }}" class="mt-4" onsubmit="return confirm('Delete all stress-test tenants and restore normal production mode?');">
            @csrf
            <input type="hidden" name="cleanup_stress_data" value="1">
            <input type="hidden" name="stress_mode" value="disable">
            <button class="rounded-md border border-red-400/50 px-4 py-2 text-sm font-bold text-red-200 hover:bg-red-400/10">Restore normal mode &amp; delete test data</button>
        </form>
    @endif
</div>
@endsection
