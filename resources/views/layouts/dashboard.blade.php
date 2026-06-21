@php
    $isAdmin = request()->is('admin/*');
    $dashboardRestaurant = $isAdmin ? null : auth()->user()?->restaurant;
    $placePlural = $dashboardRestaurant?->locationLabelTitle(true) ?? 'Tables';
    $links = $isAdmin
        ? [
            ['Admin', route('admin.dashboard')],
            ['Restaurants & Hotels', route('admin.restaurants.index')],
            ['Users', route('admin.users.index')],
            ['Demo Requests', route('admin.demo-requests.index')],
            ['Subscriptions', route('admin.subscriptions.index')],
            ['Payments', route('admin.payments.index')],
        ]
        : [
            ['Overview', route('restaurant.dashboard')],
            ['Analytics', route('restaurant.analytics')],
            ['Work Board', route('restaurant.orders.index')],
            ['Menu Items', route('restaurant.menu-items.index')],
            ['Categories', route('restaurant.categories.index')],
            [$placePlural.' / QR', route('restaurant.tables.index')],
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
    <link rel="icon" type="image/png" href="{{ asset('logo/zemtab-pantone-1795-c-icon-transparent.png') }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { zem: { bg: '#F8FAFC', card: '#FFFFFF', gold: '#D22630', cream: '#000000', muted: '#475467', green: '#16a34a', border: '#D8E0E7', red: '#D22630', redDark: '#A71D2A', ink: '#000000', coral: '#D22630', navy: '#000000', charcoal: '#000000', porcelain: '#F8FAFC', soft: '#EEF3F7' } }, fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'], display: ['Sora', 'Inter', 'ui-sans-serif'] } } } }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        [x-cloak]{display:none!important}
        input,select,textarea,button{font-size:16px}
        input:not([type="color"]):not([type="checkbox"]):not([type="radio"]),select,textarea{background-color:#fff;color:#000000}
        @keyframes slide-in{from{opacity:0;transform:translateY(-12px)}to{opacity:1;transform:translateY(0)}}
        .animate-slide-in{animation:slide-in .4s ease-out}
    </style>
</head>
<body class="bg-zem-bg text-zem-cream font-sans antialiased">
<div class="min-h-screen bg-[radial-gradient(circle_at_top_right,rgba(210,38,48,.16),transparent_28%),linear-gradient(180deg,#F8FAFC,#EEF3F7)] lg:flex">
    <aside class="border-b border-zem-border bg-zem-card/95 backdrop-blur lg:fixed lg:inset-y-0 lg:w-72 lg:border-b-0 lg:border-r">
        <div class="flex items-center justify-between px-5 py-5 lg:block">
            <a href="{{ route('home') }}" class="inline-flex items-center"><img src="{{ asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}" alt="ZemTab" class="h-12 w-auto"></a>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="rounded-lg border border-zem-border px-3 py-2 text-sm text-zem-muted transition hover:border-zem-gold hover:text-zem-gold">Logout</button></form>
        </div>
        <nav class="flex gap-2 overflow-x-auto px-4 pb-4 lg:block lg:space-y-1">
            @foreach($links as [$label, $url])
                <a href="{{ $url }}" class="block whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold transition {{ str_starts_with(url()->current(), $url) ? 'bg-zem-gold text-white shadow-lg shadow-zem-gold/20' : 'text-zem-muted hover:bg-zem-soft hover:text-zem-cream' }}">{{ $label }}</a>
            @endforeach
        </nav>
    </aside>
    <main class="w-full px-4 py-6 md:px-6 lg:ml-72 lg:px-8">
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-zem-border pb-6">
            <div>
                <p class="text-sm uppercase tracking-widest text-zem-gold">{{ $eyebrow ?? ($isAdmin ? 'SaaS Admin' : ($dashboardRestaurant?->businessTypeLabel().' Dashboard')) }}</p>
                <h1 class="font-display text-2xl font-bold md:text-4xl">{{ $heading ?? 'Dashboard' }}</h1>
            </div>
            <div class="rounded-full border border-zem-border bg-white px-4 py-2 text-sm text-zem-muted">{{ auth()->user()->name }}</div>
        </header>
        @if(session('success'))<div class="mb-5 rounded-lg border border-zem-green/40 bg-zem-green/15 px-4 py-3 text-sm text-zem-cream">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="mb-5 rounded-lg border border-red-500/40 bg-red-950/60 px-4 py-3 text-sm">{{ $errors->first() }}</div>@endif
        @php($showWarning = isset($dashboardRestaurant) && $dashboardRestaurant && $dashboardRestaurant->isExpiringSoon() && !request()->is('admin/*'))
        @if($showWarning)
            <div class="mb-5 rounded-lg border border-zem-gold/40 bg-zem-gold/10 px-4 py-3 text-sm">
                <p class="font-bold text-zem-gold">Subscription expiring soon</p>
                <p class="mt-1 text-zem-muted">Your subscription expires in {{ $dashboardRestaurant->daysUntilExpiry() }} day(s). Please pay to keep your dashboard active.</p>
                <p class="mt-2 text-zem-muted">Pay via Telebirr: <strong class="text-zem-cream">0911 000 000</strong> or Telegram: <strong class="text-zem-cream">@Zemtab</strong></p>
            </div>
        @endif
        @yield('content')
    </main>
</div>
</body>
</html>