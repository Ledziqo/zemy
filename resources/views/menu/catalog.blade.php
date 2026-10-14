@extends('layouts.app', [
    'title' => $restaurant->name.' Digital Menu | ZemTab',
    'description' => 'Browse the current menu and prices at '.$restaurant->name.'.',
    'canonical' => $table ? route('menu.show', [$restaurant->slug, $table->table_number]) : \App\Support\PublicMenuCache::catalogUrl($restaurant),
    'accentColor' => $restaurant->primary_color, 'robots' => 'index, follow', 'alpine' => true,
])

@section('content')
@php
    $photos = $restaurant->menuPhotosEnabled();
    $logoPath = $restaurant->logo_path;
    $logoUrl = $logoPath ? (\Illuminate\Support\Str::startsWith($logoPath, ['http://', 'https://', 'uploads/'])
        ? (str_starts_with($logoPath, 'uploads/') ? asset($logoPath) : $logoPath) : asset('storage/'.$logoPath)) : null;
@endphp
<main class="min-h-screen bg-neutral-100 pb-8 text-zem-ink" x-data="{ search: '', category: 'all', matches(name, description) { const q = this.search.trim().toLowerCase(); return !q || (name + ' ' + description).toLowerCase().includes(q); } }">
    <header class="sticky top-0 z-30 border-b border-black/10 bg-white px-4 py-4 shadow-sm">
        <div class="mx-auto max-w-5xl">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $restaurant->name }} logo" class="h-14 w-14 shrink-0 rounded-xl border border-black/10 bg-white object-contain p-1">@endif
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-widest text-zem-gold">{{ __('Digital menu') }}</p>
                        <h1 class="font-display text-2xl font-extrabold">{{ $restaurant->name }}</h1>
                        @if($restaurant->location)<p class="text-sm text-neutral-500">{{ $restaurant->location }}</p>@endif
                    </div>
                </div>
                <form method="post" action="{{ route('locale.update') }}">@csrf
                    <label for="catalog-locale" class="sr-only">{{ __('Language') }}</label>
                    <select id="catalog-locale" name="locale" onchange="this.form.submit()" class="rounded-full border border-black/10 bg-white px-3 py-2 text-xs font-bold">
                        @foreach(['en' => 'English', 'am' => 'አማርኛ', 'ar' => 'العربية', 'zh' => '中文'] as $code => $label)
                            <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <label class="js-only mt-4 block">
                <span class="sr-only">{{ __('Search menu') }}</span>
                <input type="search" x-model="search" placeholder="{{ __('Search the menu...') }}" class="w-full rounded-xl border border-black/10 bg-neutral-100 px-4 py-3 outline-none focus:border-zem-gold">
            </label>
            <nav aria-label="{{ __('Menu categories') }}" class="js-only mt-3 flex gap-2 overflow-x-auto pb-1">
                <button type="button" @click="category = 'all'" :class="category === 'all' ? 'bg-zem-gold text-white' : 'bg-neutral-100'" class="whitespace-nowrap rounded-full px-4 py-2 font-bold">{{ __('All') }}</button>
                @foreach($categories as $category)
                    <button type="button" @click="category = '{{ $category->id }}'" :class="category === '{{ $category->id }}' ? 'bg-zem-gold text-white' : 'bg-neutral-100'" class="whitespace-nowrap rounded-full px-4 py-2 font-bold">{{ $category->name }}</button>
                @endforeach
            </nav>
        </div>
    </header>
    <div class="mx-auto max-w-5xl space-y-8 px-4 py-6">
        @forelse($categories as $category)
            <section x-show="category === 'all' || category === '{{ $category->id }}'" aria-labelledby="catalog-category-{{ $category->id }}">
                <h2 id="catalog-category-{{ $category->id }}" class="mb-3 font-display text-2xl font-bold">{{ $category->name }}</h2>
                <div class="{{ $photos ? 'grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4' : 'space-y-3' }}">
                    @foreach($category->menuItems as $item)
                        @php($imageUrl = $photos ? \App\Support\MenuImage::url($item->image_path) : null)
                        <article x-show="matches(@js($item->name), @js($item->description ?? ''))" class="overflow-hidden rounded-xl border border-black/10 bg-white shadow-sm" itemscope itemtype="https://schema.org/MenuItem">
                            @if($imageUrl)
                                <img src="{{ $imageUrl }}" alt="{{ $item->name }}" width="480" height="480" loading="lazy" decoding="async" class="aspect-square w-full object-cover">
                            @endif
                            <div class="{{ $photos ? 'p-3' : 'flex items-start justify-between gap-3 p-4' }}">
                                <div class="min-w-0">
                                    <h3 class="font-bold" itemprop="name">{{ $item->name }}</h3>
                                    @if($item->description)<p class="mt-1 whitespace-pre-line text-sm text-neutral-500" itemprop="description">{{ $item->description }}</p>@endif
                                    @unless($item->is_available)<span class="mt-2 inline-block rounded-full bg-red-100 px-2 py-1 text-xs font-bold text-red-700">{{ __('Unavailable') }}</span>@endunless
                                </div>
                                <p class="{{ $photos ? 'mt-3' : 'shrink-0' }} font-bold text-zem-gold" itemprop="offers" itemscope itemtype="https://schema.org/Offer"><span itemprop="price" content="{{ $item->price }}">{{ number_format($item->price, (float) $item->price == floor((float) $item->price) ? 0 : 2) }}</span> <span itemprop="priceCurrency" content="ETB">ETB</span></p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <p class="rounded-xl bg-white p-6 text-neutral-500">{{ __('The menu is being updated. Please check back soon.') }}</p>
        @endforelse
        <footer class="text-center text-sm text-neutral-500">{{ __('Powered by') }} <a href="{{ route('home') }}" class="font-bold text-zem-gold">ZemTab</a></footer>
    </div>
</main>
@endsection
