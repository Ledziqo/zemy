@extends('layouts.dashboard', ['heading' => 'Database', 'eyebrow' => 'Admin Maintenance'])

@section('content')
<div class="max-w-3xl rounded-md border border-zem-border bg-zem-card p-5">
    <h2 class="font-display text-xl font-bold">Database maintenance</h2>
    <p class="mt-1 text-sm text-zem-muted">Apply pending migrations and rebuild the production config, route, and view caches after deploying code updates. Database credentials are only needed when they have changed.</p>

    <form method="post" action="{{ route('admin.setup.run') }}" class="mt-5" data-maintenance-form>
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
            <button name="maintenance" value="1" class="rounded-md bg-zem-gold px-5 py-3 font-bold text-white" data-maintenance-button>Run database maintenance</button>
            <span class="self-center text-sm text-zem-muted" data-maintenance-status aria-live="polite"></span>
        </div>
    </form>

    <p class="mt-3 text-xs text-zem-muted">This performs the server-side steps needed after a GitHub deployment. For a complete menu replacement, use the import package below.</p>

    @if(session('setup_output'))
        <div class="mt-4 rounded-md border border-zem-border bg-zem-bg p-4">
            <pre class="whitespace-pre-wrap text-sm text-zem-muted">{{ session('setup_output') }}</pre>
        </div>
    @endif
</div>

<script>
    document.querySelector('[data-maintenance-form]')?.addEventListener('submit', () => {
        const button = document.querySelector('[data-maintenance-button]');
        const status = document.querySelector('[data-maintenance-status]');
        if (button) {
            button.disabled = true;
            button.textContent = 'Running maintenance…';
        }
        if (status) status.textContent = 'Applying migrations and rebuilding caches. Please wait…';
    });
</script>

<div class="mt-6 max-w-3xl rounded-md border border-emerald-400/40 bg-zem-card p-5">
    <h2 class="font-display text-xl font-bold">Replace menu from import package</h2>
    <p class="mt-1 text-sm text-zem-muted">Upload the ZemTab ZIP package supplied for a restaurant. It replaces that restaurant's categories, descriptions, prices, availability, and photos in one transaction. Existing orders, tables, and users are preserved.</p>

    <form method="post" action="{{ route('admin.menu-import.store') }}" enctype="multipart/form-data" class="mt-5 grid gap-3 md:grid-cols-2" onsubmit="return confirm('Replace this restaurant menu completely from the uploaded package? Existing menu categories and items will be replaced.');">
        @csrf
        <label class="grid gap-1 text-sm">
            <span class="font-bold text-zem-muted">Restaurant</span>
            <select name="restaurant_id" required class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
                <option value="">Choose restaurant</option>
                @foreach($restaurants as $restaurant)
                    <option value="{{ $restaurant->id }}">{{ $restaurant->name }} ({{ $restaurant->slug }})</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-1 text-sm">
            <span class="font-bold text-zem-muted">Menu package ZIP</span>
            <input name="menu_package" type="file" accept=".zip,application/zip" required class="rounded-md border border-zem-border bg-zem-bg px-3 py-2 text-sm">
        </label>
        <button class="rounded-md bg-emerald-500 px-5 py-3 font-bold text-black md:col-span-2">Import and replace menu</button>
    </form>

    @if(session('menu_import_output'))
        <div class="mt-4 rounded-md border border-emerald-400/40 bg-emerald-400/10 p-4">
            <pre class="whitespace-pre-wrap text-sm text-emerald-100">{{ session('menu_import_output') }}</pre>
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
