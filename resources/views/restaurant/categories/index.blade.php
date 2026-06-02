@extends('layouts.dashboard', ['heading' => 'Categories'])

@section('content')
<form method="post" action="{{ route('restaurant.categories.store') }}" class="mb-6 flex flex-wrap gap-3 rounded-md border border-zem-border bg-zem-card p-4">@csrf<input name="name" required placeholder="Category name" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2"><input name="sort_order" type="number" placeholder="Sort" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2"><label class="flex items-center gap-2"><input name="is_active" type="checkbox" value="1" checked> Active</label><button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Add</button></form>
<div class="grid gap-3">
@foreach($categories as $category)
    <div class="rounded-md border border-zem-border bg-zem-card p-4"><form method="post" action="{{ route('restaurant.categories.update', $category) }}" class="flex flex-wrap items-center gap-3">@csrf @method('PATCH')<input name="name" value="{{ $category->name }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2"><input name="sort_order" type="number" value="{{ $category->sort_order }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2"><label class="flex items-center gap-2"><input name="is_active" type="checkbox" value="1" @checked($category->is_active)> Active</label><button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Save</button></form><form method="post" action="{{ route('restaurant.categories.destroy', $category) }}" class="mt-3">@csrf @method('DELETE')<button class="rounded-md border border-red-500/40 px-4 py-2 text-sm font-bold text-red-200">Delete category</button></form></div>
@endforeach
</div>
@endsection
