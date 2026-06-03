@extends('layouts.dashboard', ['heading' => 'Tables / QR Codes'])

@section('content')
<form method="post" action="{{ route('restaurant.tables.store') }}" class="mb-6 flex flex-wrap gap-3 rounded-md border border-zem-border bg-zem-card p-4">@csrf<input name="table_number" required placeholder="Table number" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2"><input name="table_name" placeholder="Table name" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2"><label class="flex items-center gap-2"><input name="is_active" type="checkbox" value="1" checked> Active</label><button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Add table</button></form>
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
@foreach($tables as $table)
    @php($url = route('menu.show', [$restaurant->slug, $table->table_number]))
    <div class="rounded-md border border-zem-border bg-zem-card p-4">
        <form method="post" action="{{ route('restaurant.tables.update', $table) }}" class="grid gap-3">@csrf @method('PATCH')<input name="table_number" value="{{ $table->table_number }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2"><input name="table_name" value="{{ $table->table_name }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2"><label class="flex items-center gap-2"><input name="is_active" type="checkbox" value="1" @checked($table->is_active)> Active</label><button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Save</button></form>
        <form method="post" action="{{ route('restaurant.tables.destroy', $table) }}" class="mt-3">@csrf @method('DELETE')<button class="rounded-md border border-red-500/40 px-4 py-2 text-sm font-bold text-red-200">Delete table</button></form>
        <div class="mt-4 rounded-md border border-zem-border bg-zem-bg p-3">
            <p class="text-xs uppercase text-zem-muted">QR URL</p>
            <a class="break-all text-sm text-zem-gold" href="{{ $url }}" target="_blank">{{ $url }}</a>
            <div class="mt-3 grid place-items-center rounded-md bg-white p-3">
                <img src="{{ route('restaurant.tables.qr', $table) }}" alt="QR code for table {{ $table->table_number }}" class="h-44 w-44">
            </div>
            <a href="{{ route('restaurant.tables.qr', $table) }}" download="zemtab-{{ $restaurant->slug }}-table-{{ $table->table_number }}.svg" class="mt-3 inline-flex rounded-md bg-zem-gold px-4 py-2 text-sm font-bold text-white">Download QR</a>
        </div>
    </div>
@endforeach
</div>
@endsection
