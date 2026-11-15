@extends('layouts.app', [
    'title' => $restaurant->name.' Digital Menu | ZemTab',
    'description' => 'Browse the current menu and prices at '.$restaurant->name.'.',
    'canonical' => $table ? route('menu.show', [$restaurant->slug, $table->table_number]) : \App\Support\PublicMenuCache::catalogUrl($restaurant),
    'accentColor' => $restaurant->primary_color, 'robots' => 'index, follow', 'alpine' => true,
])

@section('content')
@if(!empty($restaurant->settings['menu_price_note']))<p style="padding:12px 20px;text-align:center;">{{ $restaurant->settings['menu_price_note'] }}</p>@endif
@php
    $photos = $restaurant->menuPhotosEnabled();
    $logoPath = $restaurant->logo_path;
    $logoUrl = $logoPath ? (\Illuminate\Support\Str::startsWith($logoPath, ['http://', 'https://', 'uploads/'])
        ? (str_starts_with($logoPath, 'uploads/') ? asset($logoPath) : $logoPath) : asset('storage/'.$logoPath)) : null;
@endphp
@php
    $guestImageIndex = 0;
    $guestSearchItems = $categories->flatMap(fn ($category) => $category->menuItems->map(fn ($item) => ['category' => $category->id, 'name' => $item->name, 'description' => $item->description ?? '']))->values();
@endphp
@include('menu.browser-assets')
<main class="guest-menu guest-catalog min-h-screen bg-neutral-100 pb-8 text-zem-ink" x-data="guestBrowser(@js($guestSearchItems))" @keydown.escape.window="selectedItem = null">
    <header class="sticky top-0 z-30 border-b border-black/10 bg-white px-4 py-4 shadow-sm">
        <div class="mx-auto max-w-5xl">
            <div class="flex items-center justify-between gap-3">
                <div class="guest-venue-brand flex min-w-0 items-center gap-3">
                    @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $restaurant->name }} logo" class="guest-venue-logo h-14 w-14 shrink-0 rounded-xl border border-black/10 bg-white object-contain p-1">@endif
                    <div class="guest-venue-copy min-w-0">
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
                <input type="search" x-model="menuSearch" placeholder="{{ __('Search the menu...') }}" class="w-full rounded-xl border border-black/10 bg-neutral-100 px-4 py-3 outline-none focus:border-zem-gold">
            </label>
            <nav aria-label="{{ __('Menu categories') }}" class="js-only mt-3 flex gap-2 overflow-x-auto pb-1">
                <button type="button" :aria-pressed="activeCategory === 'all'" @click="activeCategory = 'all'" :class="activeCategory === 'all' ? 'bg-zem-gold text-white' : 'bg-neutral-100'" class="whitespace-nowrap rounded-full px-4 py-2 font-bold">{{ __('All') }}</button>
                @foreach($categories as $category)
                    <button type="button" :aria-pressed="activeCategory === 'cat-{{ $category->id }}'" @click="activeCategory = 'cat-{{ $category->id }}'" :class="activeCategory === 'cat-{{ $category->id }}' ? 'bg-zem-gold text-white' : 'bg-neutral-100'" class="whitespace-nowrap rounded-full px-4 py-2 font-bold">{{ __($category->name) }}</button>
                @endforeach
            </nav>
        </div>
    </header>
    <div class="mx-auto max-w-5xl space-y-8 px-4 py-6">
        @forelse($categories as $category)
            <section x-show="categoryVisible({{ $category->id }}) && categoryHasVisibleItems({{ $category->id }})" aria-labelledby="catalog-category-{{ $category->id }}">
                <h2 id="catalog-category-{{ $category->id }}" class="mb-3 font-display text-2xl font-bold">{{ __($category->name) }}</h2>
                @php
                    $sectionMap = $restaurant->settings['menu_sections'][\Illuminate\Support\Str::lower($category->name)] ?? [];
                    $sections = $category->menuItems->groupBy(fn ($item) => $sectionMap[$item->name] ?? '');
                @endphp
                @foreach($sections as $sectionName => $sectionItems)
                @if($sectionName !== '')<h3 class="mb-3 mt-6 font-display text-xl font-bold" x-show="@js($sectionItems->map(fn ($item) => ['name' => $item->name, 'description' => $item->description ?? ''])->values()).some(item => itemVisible({{ $category->id }}, item.name, item.description))">{{ $sectionName }}</h3>@endif
                <div class="{{ $photos ? 'guest-photo-grid grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4' : 'space-y-3' }}">
                    @foreach($sectionItems as $item)
                        @php($webpImageUrl = $photos ? \App\Support\MenuImage::webpUrl($item->image_path) : null)
                        @php($imageUrl = $photos ? \App\Support\MenuImage::url($item->image_path) : null)
                        <article x-show="itemVisible({{ $category->id }}, @js($item->name), @js($item->description ?? ''))" class="guest-item-card overflow-hidden rounded-xl border border-black/10 bg-white shadow-sm" itemscope itemtype="https://schema.org/MenuItem">
                            @if($imageUrl)
                                <button type="button" class="guest-photo-button" aria-label="{{ __('View details') }}: {{ $item->name }}" @click="selectedItem = { name: @js($item->name), description: @js($item->description ?? ''), image: @js($imageUrl), price: @js(number_format($item->price, (float) $item->price == floor((float) $item->price) ? 0 : 2).' ETB'), available: @js((bool) $item->is_available) }">
                                    <picture>@if($webpImageUrl)<source srcset="{{ $webpImageUrl }}" type="image/webp">@endif<img src="{{ $imageUrl }}" alt="{{ $item->name }}" width="480" height="480" loading="{{ $guestImageIndex++ < 2 ? 'eager' : 'lazy' }}" decoding="async" class="aspect-square w-full object-cover"></picture>
                                </button>
                            @endif
                            <div class="{{ $photos ? 'guest-item-copy p-3' : 'flex items-start justify-between gap-3 p-4' }}">
                                <div class="min-w-0">
                                    <h3 class="font-bold" itemprop="name"><button class="guest-item-name" type="button" @click="selectedItem = { name: @js($item->name), description: @js($item->description ?? ''), image: @js($imageUrl), price: @js(number_format($item->price, (float) $item->price == floor((float) $item->price) ? 0 : 2).' ETB'), available: @js((bool) $item->is_available) }">{{ $item->name }}</button></h3>
                                    @if($item->description)<p class="mt-1 line-clamp-2 text-sm text-neutral-500" itemprop="description">{{ $item->description }}</p>@endif
                                    @unless($item->is_available)<span class="mt-2 inline-block rounded-full bg-red-100 px-2 py-1 text-xs font-bold text-red-700">{{ __('Unavailable') }}</span>@endunless
                                </div>
                                <p class="{{ $photos ? 'mt-3' : 'shrink-0' }} font-bold text-zem-gold" itemprop="offers" itemscope itemtype="https://schema.org/Offer"><span itemprop="price" content="{{ $item->price }}">{{ number_format($item->price, (float) $item->price == floor((float) $item->price) ? 0 : 2) }}</span> <span itemprop="priceCurrency" content="ETB">ETB</span></p>
                            </div>
                        </article>
                    @endforeach
                </div>
                @endforeach
            </section>
        @empty
            <p class="rounded-xl bg-white p-6 text-neutral-500">{{ __('The menu is being updated. Please check back soon.') }}</p>
        @endforelse
        <div x-show="!hasVisibleItems()" x-cloak class="guest-empty" role="status"><h2>{{ __('No matching dishes') }}</h2><p>{{ __('Try another name or browse all categories.') }}</p><button type="button" @click="resetSearch()">{{ __('Show all items') }}</button></div>
        @include('menu.powered-by')
    </div>
    <div x-show="selectedItem" x-cloak x-effect="guestDialogState($el, !!selectedItem)" class="guest-detail-overlay" @click.self="selectedItem = null" role="dialog" aria-modal="true" aria-label="{{ __('Menu item details') }}">
        <section class="guest-detail-sheet"><template x-if="selectedItem"><div>
            <button type="button" class="guest-detail-close" @click="selectedItem = null">{{ __('Close') }}</button>
            <template x-if="selectedItem.image"><img :src="selectedItem.image" :alt="selectedItem.name" class="guest-detail-photo"></template>
            <div class="guest-detail-copy"><h2 x-text="selectedItem.name"></h2><p class="guest-detail-price" x-text="selectedItem.price"></p><p x-text="selectedItem.description" class="guest-detail-description"></p><p x-show="!selectedItem.available">{{ __('Currently unavailable') }}</p></div>
        </div></template></section>
    </div>
</main>
@endsection
