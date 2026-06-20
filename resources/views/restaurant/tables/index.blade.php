@extends('layouts.dashboard', ['heading' => $restaurant->locationLabelTitle(true).' / QR Codes'])

@section('content')
@php($place = $restaurant->locationLabel())
@php($placeTitle = $restaurant->locationLabelTitle())
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <h2 class="font-display text-lg font-bold">Add {{ $place }}</h2>
    <a href="{{ route('restaurant.tables.setup-pack') }}" target="_blank" class="rounded-md bg-zem-gold px-4 py-2 text-sm font-bold text-white">Print setup pack</a>
</div>
<form method="post" action="{{ route('restaurant.tables.store') }}" class="mb-6 grid gap-3 rounded-md border border-zem-border bg-zem-card p-4 md:grid-cols-[1fr_1fr_auto_auto]">
    @csrf
    <input name="table_number" required placeholder="{{ $placeTitle }} number, e.g. {{ $restaurant->isHotel() ? '204' : '1' }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-3">
    <input name="table_name" placeholder="{{ $placeTitle }} name optional" class="rounded-md border border-zem-border bg-zem-bg px-3 py-3">
    <label class="flex items-center gap-2 rounded-md border border-zem-border bg-zem-bg px-3 py-3"><input name="is_active" type="checkbox" value="1" checked> Active</label>
    <button class="rounded-md bg-zem-gold px-5 py-3 font-bold text-white">Generate {{ $place }} QR</button>
</form>

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
@foreach($tables as $table)
    @php($url = route('menu.show', [$restaurant->slug, $table->table_number]))
    <article class="rounded-md border border-zem-border bg-zem-card p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-zem-gold">{{ $placeTitle }} QR</p>
                <h2 class="mt-1 font-display text-2xl font-bold">{{ $table->table_name ?: $placeTitle.' '.$table->table_number }}</h2>
                <p class="text-sm text-zem-muted">{{ $placeTitle }} number {{ $table->table_number }}</p>
            </div>
            <x-status :status="$table->is_active ? 'active' : 'cancelled'" />
        </div>
        <div class="mt-4 grid place-items-center rounded-md bg-white p-4">
            <img src="{{ route('restaurant.tables.qr', $table) }}" alt="QR code for {{ $place }} {{ $table->table_number }}" class="h-56 w-56">
        </div>
        <a class="mt-3 block break-all rounded-md border border-zem-border bg-zem-bg p-3 text-sm text-zem-gold" href="{{ $url }}" target="_blank">{{ $url }}</a>
        <div class="mt-3 grid grid-cols-2 gap-2">
            <a href="{{ route('restaurant.tables.qr', $table) }}" download="zemtab-{{ $restaurant->slug }}-{{ $place }}-{{ $table->table_number }}.svg" class="rounded-md bg-zem-gold px-4 py-2 text-center text-sm font-bold text-white">Download QR</a>
            <a href="{{ $url }}" target="_blank" class="rounded-md border border-zem-border px-4 py-2 text-center text-sm font-bold">Open menu</a>
        </div>
        <details class="mt-4 rounded-md border border-zem-border bg-zem-bg p-3">
            <summary class="cursor-pointer text-sm font-bold">Edit {{ $place }}</summary>
            <form method="post" action="{{ route('restaurant.tables.update', $table) }}" class="mt-3 grid gap-3">
                @csrf @method('PATCH')
                <input name="table_number" value="{{ $table->table_number }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                <input name="table_name" value="{{ $table->table_name }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                <label class="flex items-center gap-2"><input name="is_active" type="checkbox" value="1" @checked($table->is_active)> Active</label>
                <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Save {{ $place }}</button>
            </form>
            <form method="post" action="{{ route('restaurant.tables.destroy', $table) }}" class="mt-3">@csrf @method('DELETE')<button class="rounded-md border border-red-300 bg-red-50 px-4 py-2 text-sm font-bold text-red-700 transition hover:border-red-500 hover:bg-red-100">Delete {{ $place }}</button></form>
        </details>
    </article>
@endforeach
</div>
<div class="mt-5">{{ $tables->links() }}</div>
@endsection
