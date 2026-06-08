@php
    $accentColor = $accentColor ?? '#F84C47';
    $accentColor = is_string($accentColor) && preg_match('/^#[0-9A-Fa-f]{6}$/', $accentColor) ? $accentColor : '#F84C47';
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}" style="--zem-accent: {{ $accentColor }};">
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
    <link rel="icon" type="image/png" href="{{ asset('logo/zemtab-icon-transparent-porcelain-coral.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo/zemtab-icon-transparent-porcelain-coral.png') }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    <meta property="og:title" content="{{ $title ?? 'ZemTab' }}">
    <meta property="og:description" content="{{ $description ?? 'ZemTab is a modern QR menu, table ordering, waiter request, and restaurant dashboard system built for restaurants in Ethiopia. Scan. Order. Pay.' }}">
    <meta property="og:image" content="{{ $ogImage ?? asset('logo/zemtab-full-transparent-porcelain-coral.png') }}">
    <meta property="og:locale" content="en_ET">
    <meta property="og:site_name" content="ZemTab">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $canonical ?? url()->current() }}">
    <meta name="twitter:title" content="{{ $title ?? 'ZemTab' }}">
    <meta name="twitter:description" content="{{ $description ?? 'ZemTab is a modern QR menu, table ordering, waiter request, and restaurant dashboard system built for restaurants in Ethiopia. Scan. Order. Pay.' }}">
    <meta name="twitter:image" content="{{ $ogImage ?? asset('logo/zemtab-full-transparent-porcelain-coral.png') }}">

    {{-- Performance: DNS Prefetch & Preconnect --}}
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="dns-prefetch" href="//cdn.tailwindcss.com">
    <link rel="dns-prefetch" href="//unpkg.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    {{-- Structured Data Injection --}}
    @stack('structured-data')

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        zem: {
                            bg: '#F7F7F4',
                            card: '#FFFFFF',
                            gold: @js($accentColor),
                            cream: '#18181B',
                            muted: '#71717A',
                            green: '#16a34a',
                            border: '#DADAD6',
                            red: '#F84C47',
                            redDark: '#C83D39',
                            ink: '#18181B',
                            coral: '#F84C47',
                            charcoal: '#232323',
                            porcelain: '#F7F7F4',
                            soft: '#F1F1EE'
                        }
                    },
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'], display: ['Sora', 'Inter', 'ui-sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <style>[x-cloak]{display:none!important}</style>
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
</body>
</html>
