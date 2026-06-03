@extends('layouts.dashboard', ['heading' => 'Menu Items'])

@section('content')
<form method="post" action="{{ route('restaurant.menu-items.store') }}" enctype="multipart/form-data" class="mb-6 grid gap-3 rounded-md border border-zem-border bg-zem-card p-4 md:grid-cols-2 xl:grid-cols-5">
    @csrf
    <select name="category_id" class="rounded-md border border-zem-border bg-zem-bg px-3 py-3">@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
    <input name="name" required placeholder="Item name" class="rounded-md border border-zem-border bg-zem-bg px-3 py-3">
    <input name="price" required type="number" step="0.01" placeholder="Price" class="rounded-md border border-zem-border bg-zem-bg px-3 py-3">
    <input name="image" type="file" accept="image/*" class="rounded-md border border-zem-border bg-zem-bg px-3 py-3 text-sm">
    <div class="flex flex-wrap items-center gap-4 rounded-md border border-zem-border bg-zem-bg px-3 py-3">
        <label class="flex items-center gap-2"><input name="is_available" type="checkbox" value="1" checked> Available</label>
        <label class="flex items-center gap-2"><input name="is_featured" type="checkbox" value="1"> Featured</label>
    </div>
    <textarea name="description" placeholder="Description" class="rounded-md border border-zem-border bg-zem-bg px-3 py-3 md:col-span-2 xl:col-span-4"></textarea>
    <button class="rounded-md bg-zem-gold px-4 py-3 font-bold text-white">Add item</button>
</form>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
@foreach($items as $menuItem)
    @php($imageUrl = $menuItem->image_path ? (\Illuminate\Support\Str::startsWith($menuItem->image_path, ['http://', 'https://', 'uploads/']) ? (str_starts_with($menuItem->image_path, 'uploads/') ? asset($menuItem->image_path) : $menuItem->image_path) : asset('storage/'.$menuItem->image_path)) : null)
    <article class="overflow-hidden rounded-md border border-zem-border bg-zem-card">
        <div class="relative aspect-square bg-zem-bg">
            @if($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $menuItem->name }}" class="h-full w-full object-cover">
            @else
                <div class="grid h-full place-items-center bg-[linear-gradient(135deg,#111,#2a0710)] text-5xl font-extrabold text-white">{{ strtoupper(substr($menuItem->name, 0, 1)) }}</div>
            @endif
            <div class="absolute left-3 top-3"><x-status :status="$menuItem->is_available ? 'active' : 'cancelled'" /></div>
            <div class="absolute bottom-3 right-3 rounded-full bg-black/80 px-3 py-1 text-sm font-extrabold text-white">{{ number_format($menuItem->price) }} ETB</div>
        </div>
        <div class="p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-zem-gold">{{ $menuItem->category?->name }}</p>
                    <h2 class="mt-1 font-display text-xl font-bold">{{ $menuItem->name }}</h2>
                </div>
                @if($menuItem->is_featured)<span class="rounded-full border border-zem-gold/40 px-2 py-1 text-xs font-bold text-zem-gold">Featured</span>@endif
            </div>
            <p class="mt-2 min-h-10 text-sm text-zem-muted">{{ $menuItem->description ?: 'No description yet.' }}</p>

            <details class="mt-4 rounded-md border border-zem-border bg-zem-bg p-3">
                <summary class="cursor-pointer text-sm font-bold text-white">Edit item</summary>
                <form method="post" action="{{ route('restaurant.menu-items.update', $menuItem) }}" enctype="multipart/form-data" class="mt-3 grid gap-3">
                    @csrf @method('PATCH')
                    <select name="category_id" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">@foreach($categories as $category)<option value="{{ $category->id }}" @selected($menuItem->category_id===$category->id)>{{ $category->name }}</option>@endforeach</select>
                    <input name="name" value="{{ $menuItem->name }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <input name="price" value="{{ $menuItem->price }}" type="number" step="0.01" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <input name="image" type="file" accept="image/*" class="rounded-md border border-zem-border bg-zem-card px-3 py-2 text-sm">
                    <textarea name="description" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">{{ $menuItem->description }}</textarea>
                    <div class="flex flex-wrap gap-4 text-sm">
                        <label class="flex items-center gap-2"><input name="is_available" type="checkbox" value="1" @checked($menuItem->is_available)> Available</label>
                        <label class="flex items-center gap-2"><input name="is_featured" type="checkbox" value="1" @checked($menuItem->is_featured)> Featured</label>
                    </div>
                    <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Save</button>
                </form>
                <form method="post" action="{{ route('restaurant.menu-items.destroy', $menuItem) }}" class="mt-3">@csrf @method('DELETE')<button class="rounded-md border border-red-500/40 px-4 py-2 text-sm font-bold text-red-200">Delete item</button></form>
            </details>
        </div>
    </article>
@endforeach
</div>
<div class="mt-5">{{ $items->links() }}</div>
@endsection
