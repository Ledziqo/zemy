@extends('layouts.dashboard', ['heading' => 'Menu Items'])

@section('content')
<form method="post" action="{{ route('restaurant.menu-items.store') }}" class="mb-6 grid gap-3 rounded-md border border-zem-border bg-zem-card p-4 md:grid-cols-4">
    @csrf
    <select name="category_id" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
    <input name="name" required placeholder="Item name" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
    <input name="price" required type="number" step="0.01" placeholder="Price" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
    <input name="image_path" placeholder="Image path" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
    <textarea name="description" placeholder="Description" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2 md:col-span-2"></textarea>
    <label class="flex items-center gap-2"><input name="is_available" type="checkbox" value="1" checked> Available</label>
    <label class="flex items-center gap-2"><input name="is_featured" type="checkbox" value="1"> Featured</label>
    <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white md:col-span-4">Add item</button>
</form>
<div class="grid gap-3">
@foreach($items as $menuItem)
    <div class="rounded-md border border-zem-border bg-zem-card p-4">
    <form method="post" action="{{ route('restaurant.menu-items.update', $menuItem) }}" class="grid gap-3 md:grid-cols-6">
        @csrf @method('PATCH')
        <select name="category_id" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">@foreach($categories as $category)<option value="{{ $category->id }}" @selected($menuItem->category_id===$category->id)>{{ $category->name }}</option>@endforeach</select>
        <input name="name" value="{{ $menuItem->name }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <input name="price" value="{{ $menuItem->price }}" type="number" step="0.01" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <input name="image_path" value="{{ $menuItem->image_path }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <label class="flex items-center gap-2"><input name="is_available" type="checkbox" value="1" @checked($menuItem->is_available)> Available</label>
        <label class="flex items-center gap-2"><input name="is_featured" type="checkbox" value="1" @checked($menuItem->is_featured)> Featured</label>
        <textarea name="description" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2 md:col-span-4">{{ $menuItem->description }}</textarea>
        <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Save</button>
    </form>
    <form method="post" action="{{ route('restaurant.menu-items.destroy', $menuItem) }}" class="mt-3">@csrf @method('DELETE')<button class="rounded-md border border-red-500/40 px-4 py-2 text-sm font-bold text-red-200">Delete item</button></form>
    </div>
@endforeach
</div>
<div class="mt-5">{{ $items->links() }}</div>
@endsection
