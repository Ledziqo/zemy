@php
    $accentColor = $accentColor ?? '#D22630';
    $accentColor = is_string($accentColor) && preg_match('/^#[0-9A-Fa-f]{6}$/', $accentColor) ? $accentColor : '#D22630';
    $accentRgb = implode(' ', sscanf($accentColor, '#%02x%02x%02x'));
@endphp
<!doctype html>
<html class="no-js" lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" style="--zem-accent: {{ $accentColor }}; --zem-accent-rgb: {{ $accentRgb }};">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    {{-- Primary Meta --}}
    <title>{{ $title ?? 'ZemTab' }}</title>
    <meta name="description" content="{{ $description ?? 'ZemTab is a modern QR menu, table ordering, waiter request, and restaurant dashboard system built for restaurants in Ethiopia. Scan. Order. Pay.' }}">
    <meta name="keywords" content="{{ $keywords ?? 'QR menu, restaurant ordering, table ordering, Ethiopia, Addis Ababa, restaurant app, digital menu, waiter call, bill request, restaurant POS, food ordering' }}">
    <meta name="author" content="ZemTab">
    <meta name="robots" content="{{ $robots ?? 'index, follow' }}">
    <meta name="theme-color" content="{{ $accentColor }}">

    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">

    {{-- Favicon --}}
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon-red.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon-red.png') }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    <meta property="og:title" content="{{ $title ?? 'ZemTab' }}">
    <meta property="og:description" content="{{ $description ?? 'ZemTab is a modern QR menu, table ordering, waiter request, and restaurant dashboard system built for restaurants in Ethiopia. Scan. Order. Pay.' }}">
    <meta property="og:image" content="{{ $ogImage ?? asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}">
    <meta property="og:locale" content="{{ ['am' => 'am_ET', 'ar' => 'ar_SA', 'zh' => 'zh_CN'][app()->getLocale()] ?? 'en_ET' }}">
    <meta property="og:site_name" content="ZemTab">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $canonical ?? url()->current() }}">
    <meta name="twitter:title" content="{{ $title ?? 'ZemTab' }}">
    <meta name="twitter:description" content="{{ $description ?? 'ZemTab is a modern QR menu, table ordering, waiter request, and restaurant dashboard system built for restaurants in Ethiopia. Scan. Order. Pay.' }}">
    <meta name="twitter:image" content="{{ $ogImage ?? asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}">

    {{-- Structured Data Injection --}}
    @stack('structured-data')

    <script>document.documentElement.classList.replace('no-js', 'js');</script>
    @include('components.frontend-assets', ['alpine' => $alpine ?? false])
    @if($offlineMenu ?? false)
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    @endif
    <style>[x-cloak]{display:none!important}.no-js .js-only{display:none!important}</style>
</head>
<body class="bg-zem-bg text-zem-cream font-sans antialiased">
    <div class="min-h-screen">
        @if(session('success'))
            <div class="fixed top-4 left-1/2 z-50 -translate-x-1/2 rounded-lg border border-zem-green/40 bg-zem-green px-4 py-3 text-sm font-semibold text-white shadow-xl">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="fixed top-4 left-1/2 z-50 max-w-md -translate-x-1/2 rounded-lg border border-red-400/50 bg-red-950 px-4 py-3 text-sm text-white shadow-xl">{{ $errors->first() }}</div>
        @endif
        {{ $slot ?? '' }}
        @yield('content')
    </div>
    @if($offlineMenu ?? false)
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => navigator.serviceWorker.register('{{ asset('sw.js') }}', {scope: '/'}).catch(() => {}));
            }
        </script>
    @endif
</body>
</html>
