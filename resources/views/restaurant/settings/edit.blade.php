@extends('layouts.dashboard', ['heading' => 'Settings'])

@section('content')
<form method="post" action="{{ route('restaurant.settings.update') }}" class="grid max-w-3xl gap-4 rounded-md border border-zem-border bg-zem-card p-5 md:grid-cols-2">
    @csrf @method('PATCH')
    <input name="name" value="{{ $restaurant->name }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
    <input name="slug" value="{{ $restaurant->slug }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
    <input name="phone" value="{{ $restaurant->phone }}" placeholder="Phone" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
    <input name="location" value="{{ $restaurant->location }}" placeholder="Location" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
    <input name="logo_path" value="{{ $restaurant->logo_path }}" placeholder="Logo path" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
    <input name="cover_image_path" value="{{ $restaurant->cover_image_path }}" placeholder="Cover image path" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
    <input name="primary_color" value="{{ $restaurant->primary_color }}" placeholder="#ef233c" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
    <input name="service_charge_percentage" value="{{ $restaurant->settings['service_charge_percentage'] ?? 0 }}" type="number" step="0.01" placeholder="Service charge %" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
    <input name="vat_percentage" value="{{ $restaurant->settings['vat_percentage'] ?? 0 }}" type="number" step="0.01" placeholder="VAT %" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
    <button class="rounded-md bg-zem-gold px-4 py-3 font-bold text-white md:col-span-2">Save settings</button>
</form>
@endsection
