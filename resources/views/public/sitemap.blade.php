@php
echo '<'.'?'.'xml version="1.0" encoding="UTF-8"?'.'>';
@endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
    {{-- Home Page --}}
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
        <image:image>
            <image:loc>{{ asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}</image:loc>
            <image:title>ZemTab Logo</image:title>
            <image:caption>ZemTab — QR Menu & Table Ordering for Restaurants in Ethiopia</image:caption>
        </image:image>
    </url>

    {{-- Restaurant Menu Pages --}}
    @foreach($restaurants as $restaurant)
        @foreach($restaurant->tables as $table)
            <url>
                <loc>{{ route('menu.show', [$restaurant->slug, $table->table_number]) }}</loc>
                <lastmod>{{ $restaurant->updated_at->toIso8601String() }}</lastmod>
                <changefreq>daily</changefreq>
                <priority>0.8</priority>
                @if($restaurant->logo_path)
                <image:image>
                    <image:loc>{{ asset($restaurant->logo_path) }}</image:loc>
                    <image:title>{{ htmlspecialchars($restaurant->name, ENT_XML1) }} Logo</image:title>
                </image:image>
                @endif
            </url>
        @endforeach
    @endforeach
</urlset>
