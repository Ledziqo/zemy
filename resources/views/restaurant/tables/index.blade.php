@extends('layouts.dashboard', ['heading' => 'Tables & Rooms / QR'])

@section('content')
@php($place = $restaurant->locationLabel())
@php($placeTitle = $restaurant->locationLabelTitle())
@php($tablePackOrientation = $qrCardDesigns['table']['preferred_orientation'])
@php($roomPackOrientation = $qrCardDesigns['room']['preferred_orientation'])
@include('restaurant.tables.design-settings')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <h2 class="font-display text-lg font-bold">{{ __('Add table or room QR') }}</h2>
    <button type="button" id="open-qr-pack-options" class="rounded-md bg-zem-gold px-4 py-2 text-sm font-bold text-white">{{ __('Print setup pack') }}</button>
</div>
<style>
.qr-pack-dialog{width:min(92vw,42rem);max-height:90vh;padding:0;border:1px solid #ffffff30;border-radius:14px;background:#191b22;color:#f4f1ec;box-shadow:0 24px 80px #0009}.qr-pack-dialog::backdrop{background:#08090cbb;backdrop-filter:blur(3px)}.qr-pack-dialog-inner{padding:24px}.qr-pack-dialog h2{margin:0;font-size:22px;font-weight:800}.qr-pack-dialog p{margin:6px 0 18px;color:#b9bfca}.qr-pack-options{display:grid;grid-template-columns:1fr 1fr;gap:14px}.qr-pack-options fieldset{min-width:0;margin:0;padding:14px;border:1px solid #ffffff24;border-radius:10px}.qr-pack-options legend{padding:0 5px;font-weight:700}.qr-pack-options label{display:block;margin:10px 0 4px;font-size:13px;color:#c5cad3}.qr-pack-options select{width:100%;padding:10px;border:1px solid #ffffff30;border-radius:7px;background:#101116;color:#fff}.qr-pack-dialog-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}.qr-pack-dialog-actions button{padding:10px 15px;border:1px solid #ffffff36;border-radius:7px;background:transparent;color:#fff;font-weight:700;cursor:pointer}.qr-pack-dialog-actions button[type=submit]{border-color:#d4b365;background:#b68b36;color:#17130a}@media(max-width:560px){.qr-pack-options{grid-template-columns:1fr}.qr-pack-dialog-inner{padding:18px}}
</style>
<dialog class="qr-pack-dialog" id="qr-pack-options" aria-labelledby="qr-pack-options-title">
    <form method="get" action="{{ route('restaurant.tables.setup-pack') }}" target="_blank" id="qr-pack-options-form" class="qr-pack-dialog-inner">
        <h2 id="qr-pack-options-title">Print setup pack</h2>
        <p>Choose the orientation and physical card size for each group. These choices apply to this print run; your saved editor designs stay unchanged.</p>
        <div class="qr-pack-options">
            <fieldset>
                <legend>Table & lobby cards</legend>
                <label for="table-pack-orientation">Orientation</label>
                <select id="table-pack-orientation" name="table_orientation"><option value="portrait" @selected($tablePackOrientation === 'portrait')>Portrait</option><option value="landscape" @selected($tablePackOrientation === 'landscape')>Landscape</option></select>
                <label for="table-pack-size">Card size</label>
                @php($tablePackSize = $qrCardDesigns['table']['orientations'][$tablePackOrientation]['card_size'] ?? 'large')
                <select id="table-pack-size" name="table_size"><option value="small" @selected($tablePackSize === 'small')>Small · 50 × 93.3 mm</option><option value="medium" @selected($tablePackSize === 'medium')>Medium · 62.5 × 116.7 mm</option><option value="large" @selected($tablePackSize === 'large')>Large · current size</option></select>
            </fieldset>
            <fieldset>
                <legend>Hotel room cards</legend>
                <label for="room-pack-orientation">Orientation</label>
                <select id="room-pack-orientation" name="room_orientation"><option value="portrait" @selected($roomPackOrientation === 'portrait')>Portrait</option><option value="landscape" @selected($roomPackOrientation === 'landscape')>Landscape</option></select>
                <label for="room-pack-size">Card size</label>
                @php($roomPackSize = $qrCardDesigns['room']['orientations'][$roomPackOrientation]['card_size'] ?? 'large')
                <select id="room-pack-size" name="room_size"><option value="small" @selected($roomPackSize === 'small')>Small · 50 × 93.3 mm</option><option value="medium" @selected($roomPackSize === 'medium')>Medium · 62.5 × 116.7 mm</option><option value="large" @selected($roomPackSize === 'large')>Large · current size</option></select>
            </fieldset>
        </div>
        <div class="qr-pack-dialog-actions"><button type="button" id="close-qr-pack-options">Cancel</button><button type="submit">Build print pack</button></div>
    </form>
</dialog>
<script>
(()=>{const dialog=document.getElementById('qr-pack-options');document.getElementById('open-qr-pack-options').addEventListener('click',()=>dialog.showModal());document.getElementById('close-qr-pack-options').addEventListener('click',()=>dialog.close());document.getElementById('qr-pack-options-form').addEventListener('submit',()=>dialog.close());})();
</script>
<form method="post" action="{{ route('restaurant.tables.store') }}" class="mb-6 grid gap-3 rounded-md border border-zem-border bg-zem-card p-4 md:grid-cols-[1fr_1fr_1fr_auto_auto]">
    @csrf
    <input name="table_number" required placeholder="Table or room number, e.g. 1 or 204" class="rounded-md border border-zem-border bg-zem-bg px-3 py-3">
    <select name="location_type" required class="rounded-md border border-zem-border bg-zem-bg px-3 py-3">
        <option value="table">Table</option>
        <option value="room">Room</option>
    </select>
    <input name="table_name" placeholder="Custom name optional" class="rounded-md border border-zem-border bg-zem-bg px-3 py-3">
    <label class="flex items-center gap-2 rounded-md border border-zem-border bg-zem-bg px-3 py-3"><input name="is_active" type="checkbox" value="1" checked> {{ __('Active') }}</label>
    <button class="rounded-md bg-zem-gold px-5 py-3 font-bold text-white">{{ __('Generate QR') }}</button>
</form>

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
@foreach($tables as $table)
    @php($url = route('menu.show', [$restaurant->slug, $table->table_number]))
    <article class="rounded-md border border-zem-border bg-zem-card p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-zem-gold">{{ $table->locationTypeLabel() }} QR</p>
                <h2 class="mt-1 font-display text-2xl font-bold">{{ $table->displayLabel() }}</h2>
                <p class="text-sm text-zem-muted">QR identifier: {{ $table->table_number }}</p>
            </div>
            <x-status :status="$table->is_active ? 'active' : 'cancelled'" />
        </div>
        <div class="mt-4 grid place-items-center rounded-md bg-white p-4">
            <img src="{{ route('restaurant.tables.qr', $table) }}" alt="QR code for {{ $table->locationTypeLabel() }} {{ $table->table_number }}" class="h-64 w-64">
        </div>
        <a class="mt-3 block break-all rounded-md border border-zem-border bg-zem-bg p-3 text-sm text-zem-gold" href="{{ $url }}" target="_blank">{{ $url }}</a>
        <div class="mt-2 flex items-center justify-center gap-2"><span class="text-xs font-semibold text-zem-muted">Powered by</span><img src="{{ asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}" alt="ZemTab" class="h-5 w-auto"></div>
        <div class="mt-3 grid grid-cols-2 gap-2">
            <a href="{{ route('restaurant.tables.qr', $table) }}" download="zemtab-{{ $restaurant->slug }}-{{ $place }}-{{ $table->table_number }}.svg" class="rounded-md bg-zem-gold px-4 py-2 text-center text-sm font-bold text-white">{{ __('Download QR') }}</a>
            <a href="{{ $url }}" target="_blank" class="rounded-md border border-zem-border px-4 py-2 text-center text-sm font-bold">{{ __('Open menu') }}</a>
        </div>
        <details class="mt-4 rounded-md border border-zem-border bg-zem-bg p-3">
            <summary class="cursor-pointer text-sm font-bold">Edit {{ $place }}</summary>
            <form method="post" action="{{ route('restaurant.tables.update', $table) }}" class="mt-3 grid gap-3">
                @csrf @method('PATCH')
                <input name="table_number" value="{{ $table->table_number }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                <select name="location_type" required class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <option value="table" @selected($table->locationTypeLabel() === 'Table')>Table</option>
                    <option value="room" @selected($table->locationTypeLabel() === 'Room')>Room</option>
                </select>
                <input name="table_name" value="{{ $table->table_name }}" placeholder="Custom name optional" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
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
