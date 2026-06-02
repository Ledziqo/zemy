<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'ZemTab' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo/zemtab-icon-transparent.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        zem: {
                            bg: '#050505',
                            card: '#101010',
                            gold: '#ef233c',
                            cream: '#ffffff',
                            muted: '#a3a3a3',
                            green: '#16a34a',
                            border: '#262626',
                            red: '#ef233c',
                            redDark: '#b91c1c',
                            ink: '#0a0a0a'
                        }
                    },
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'], display: ['Sora', 'Inter', 'ui-sans-serif'] }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
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
