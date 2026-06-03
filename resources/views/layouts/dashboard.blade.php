@php
    $isAdmin = request()->is('admin/*');
    $links = $isAdmin
        ? [
            ['Admin', route('admin.dashboard')],
            ['Restaurants', route('admin.restaurants.index')],
            ['Users', route('admin.users.index')],
            ['Demo Requests', route('admin.demo-requests.index')],
            ['Subscriptions', route('admin.subscriptions.index')],
            ['Orders', route('admin.orders.index')],
        ]
        : [
            ['Overview', route('restaurant.dashboard')],
            ['Orders', route('restaurant.orders.index')],
            ['Menu Items', route('restaurant.menu-items.index')],
            ['Categories', route('restaurant.categories.index')],
            ['Tables / QR', route('restaurant.tables.index')],
            ['Service Requests', route('restaurant.service-requests.index')],
            ['Settings', route('restaurant.settings.edit')],
        ];
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    <title>{{ $title ?? 'ZemTab Dashboard' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo/zemtab-icon-transparent.png') }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { zem: { bg: '#050505', card: '#101010', gold: '#ef233c', cream: '#ffffff', muted: '#a3a3a3', green: '#16a34a', border: '#262626', red: '#ef233c', redDark: '#b91c1c', ink: '#0a0a0a' } }, fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'], display: ['Sora', 'Inter', 'ui-sans-serif'] } } } }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <style>[x-cloak]{display:none!important} input,select,textarea,button{font-size:16px}</style>
</head>
<body class="bg-zem-bg text-zem-cream font-sans antialiased">
<div class="min-h-screen bg-[radial-gradient(circle_at_top_right,rgba(239,35,60,.18),transparent_28%),linear-gradient(180deg,#050505,#0a0a0a)] lg:flex">
    <aside class="border-b border-zem-border bg-zem-card/95 backdrop-blur lg:fixed lg:inset-y-0 lg:w-72 lg:border-b-0 lg:border-r">
        <div class="flex items-center justify-between px-5 py-5 lg:block">
            <a href="{{ route('home') }}" class="inline-flex items-center"><img src="{{ asset('logo/zemtab-full-transparent-dark.png') }}" alt="ZemTab" class="h-12 w-auto"></a>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="rounded-lg border border-zem-border px-3 py-2 text-sm text-zem-muted transition hover:border-zem-gold hover:text-white">Logout</button></form>
        </div>
        <nav class="flex gap-2 overflow-x-auto px-4 pb-4 lg:block lg:space-y-1">
            @foreach($links as [$label, $url])
                <a href="{{ $url }}" class="block whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold transition {{ url()->current() === $url ? 'bg-zem-gold text-white shadow-lg shadow-zem-gold/20' : 'text-zem-muted hover:bg-white/5 hover:text-zem-cream' }}">{{ $label }}</a>
            @endforeach
        </nav>
    </aside>
    <main class="w-full px-4 py-6 md:px-6 lg:ml-72 lg:px-8">
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-white/10 pb-6">
            <div>
                <p class="text-sm uppercase tracking-widest text-zem-gold">{{ $eyebrow ?? ($isAdmin ? 'SaaS Admin' : 'Restaurant Dashboard') }}</p>
                <h1 class="font-display text-2xl font-bold md:text-4xl">{{ $heading ?? 'Dashboard' }}</h1>
            </div>
            <div class="rounded-full border border-zem-border bg-white/5 px-4 py-2 text-sm text-zem-muted">{{ auth()->user()->name }}</div>
        </header>
        @if(session('success'))<div class="mb-5 rounded-lg border border-zem-green/40 bg-zem-green/15 px-4 py-3 text-sm text-zem-cream">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="mb-5 rounded-lg border border-red-500/40 bg-red-950/60 px-4 py-3 text-sm">{{ $errors->first() }}</div>@endif
        @yield('content')
    </main>
</div>
</body>
</html>
