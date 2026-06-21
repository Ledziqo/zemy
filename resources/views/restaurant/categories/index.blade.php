@extends('layouts.dashboard', ['heading' => __('Categories')])

@section('content')
<div class="mb-6 rounded-md border border-zem-border bg-zem-card p-4">
    <h2 class="mb-3 font-display text-lg font-bold">{{ __('Add new category') }}</h2>
    <form method="post" action="{{ route('restaurant.categories.store') }}" class="flex flex-wrap gap-3">
        @csrf
        <input name="name" required placeholder="{{ __('Category name') }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <input name="sort_order" type="number" placeholder="{{ __('Sort order') }}" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <label class="flex items-center gap-2 rounded-md border border-zem-border bg-zem-bg px-3 py-2"><input name="is_active" type="checkbox" value="1" checked> {{ __('Active') }}</label>
        <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">{{ __('Add category') }}</button>
    </form>
</div>

<div class="grid gap-3">
    @forelse($categories as $category)
        <div class="rounded-md border border-zem-border bg-zem-card p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div>
                        <h3 class="font-display text-lg font-bold">{{ $category->name }}</h3>
                        <p class="text-sm text-zem-muted">Sort order: {{ $category->sort_order }} - {{ $category->is_active ? 'Active' : 'Inactive' }}</p>
                    </div>
                </div>
            </div>
            <details class="mt-3 rounded-md border border-zem-border bg-zem-bg p-3">
                <summary class="cursor-pointer text-sm font-bold text-zem-cream">{{ __('Edit category') }}</summary>
                <form method="post" action="{{ route('restaurant.categories.update', $category) }}" class="mt-3 flex flex-wrap items-center gap-3">
                    @csrf @method('PATCH')
                    <input name="name" value="{{ $category->name }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <input name="sort_order" type="number" value="{{ $category->sort_order }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <label class="flex items-center gap-2"><input name="is_active" type="checkbox" value="1" @checked($category->is_active)> Active</label>
                    <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">{{ __('Save') }}</button>
                </form>
                <form method="post" action="{{ route('restaurant.categories.destroy', $category) }}" class="mt-3">@csrf @method('DELETE')<button class="rounded-md border border-red-300 bg-red-50 px-4 py-2 text-sm font-bold text-red-700 transition hover:border-red-500 hover:bg-red-100">{{ __('Delete category') }}</button></form>
            </details>
        </div>
    @empty
        <div class="rounded-md border border-zem-border bg-zem-card p-8 text-center">
            <div class="text-3xl mb-2">📂</div>
            <p class="text-zem-muted">No categories yet. Add your first category above to organize your menu.</p>
        </div>
    @endforelse
</div>
@endsection
