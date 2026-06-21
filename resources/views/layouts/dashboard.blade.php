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
            ['Payments', route('admin.payments.index')],\n            ['Payment Settings', route('admin.payment-settings.index')],
        ]
        : [
            [__('Overview'), route('restaurant.dashboard')],
            [__('Analytics'), route('restaurant.analytics')],
            [__('Work Board'), route('restaurant.orders.index')],
            [__('Menu Items'), route('restaurant.menu-items.index')],
            [__('Categories'), route('restaurant.categories.index')],
            [$placePlural.' / QR', route('restaurant.tables.index')],
            [__('Settings'), route('restaurant.settings.edit')],
        ];
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    <title>{{ $title ?? 'ZemTab Dashboard' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo/zemtab-pantone-1795-c-icon-transparent.png') }}">
    <link rel="canonical" href="{{ url()->current() }}">
    @unless($isAdmin)<script>
        (() => {
            const saved = localStorage.getItem('zemtabTheme');
            const dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>@endunless
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { zem: { bg: 'rgb(var(--zem-bg) / <alpha-value>)', card: 'rgb(var(--zem-card) / <alpha-value>)', gold: '#D22630', cream: 'rgb(var(--zem-text) / <alpha-value>)', muted: 'rgb(var(--zem-muted) / <alpha-value>)', green: '#16a34a', border: 'rgb(var(--zem-border) / <alpha-value>)', red: '#D22630', redDark: '#A71D2A', ink: 'rgb(var(--zem-text) / <alpha-value>)', coral: '#D22630', navy: 'rgb(var(--zem-text) / <alpha-value>)', charcoal: 'rgb(var(--zem-text) / <alpha-value>)', porcelain: 'rgb(var(--zem-bg) / <alpha-value>)', soft: 'rgb(var(--zem-soft) / <alpha-value>)' } }, fontFamily: { sans: ['Inter', 'Noto Sans Ethiopic', 'ui-sans-serif', 'system-ui'], display: ['Sora', 'Noto Sans Ethiopic', 'Inter', 'ui-sans-serif'] } } } }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Ethiopic:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--zem-bg:248 250 252;--zem-card:255 255 255;--zem-text:0 0 0;--zem-muted:71 84 103;--zem-border:216 224 231;--zem-soft:238 243 247;color-scheme:light}
        .dark{--zem-bg:15 23 42;--zem-card:30 41 59;--zem-text:241 245 249;--zem-muted:148 163 184;--zem-border:71 85 105;--zem-soft:51 65 85;color-scheme:dark}
        .dark .bg-white{background-color:rgb(var(--zem-card))!important}.dark .bg-neutral-50,.dark .bg-neutral-100{background-color:rgb(var(--zem-soft))!important}.dark .text-black,.dark .text-neutral-700{color:rgb(var(--zem-text))!important}.dark .text-neutral-500,.dark .text-neutral-600{color:rgb(var(--zem-muted))!important}
        [x-cloak]{display:none!important}
        input,select,textarea,button{font-size:16px}
        input:not([type="color"]):not([type="checkbox"]):not([type="radio"]),select,textarea{background-color:rgb(var(--zem-card));color:rgb(var(--zem-text))}
        @keyframes slide-in{from{opacity:0;transform:translateY(-12px)}to{opacity:1;transform:translateY(0)}}
        .animate-slide-in{animation:slide-in .4s ease-out}
    </style>
</head>
<body class="bg-zem-bg text-zem-cream font-sans antialiased">
<div class="min-h-screen bg-zem-bg lg:flex" style="background-image:radial-gradient(circle at top right,rgba(210,38,48,.16),transparent 28%)">
    <aside class="border-b border-zem-border bg-zem-card/95 backdrop-blur lg:fixed lg:inset-y-0 lg:w-72 lg:border-b-0 lg:border-r">
        <div class="flex items-center justify-between px-5 py-5 lg:block">
            <a href="{{ route('home') }}" class="inline-flex items-center"><img src="{{ asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}" alt="ZemTab" class="h-12 w-auto"></a>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="rounded-lg border border-zem-border px-3 py-2 text-sm text-zem-muted transition hover:border-zem-gold hover:text-zem-gold">{{ __('Logout') }}</button></form>
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
            <div class="flex flex-wrap items-center gap-2">
                @unless($isAdmin)
                    <form method="post" action="{{ route('locale.update') }}">@csrf<input type="hidden" name="locale" value="{{ app()->getLocale() === 'am' ? 'en' : 'am' }}"><button class="rounded-full border border-zem-border bg-zem-card px-3 py-2 text-sm font-bold text-zem-muted">{{ app()->getLocale() === 'am' ? 'English' : 'አማርኛ' }}</button></form>
                    <button type="button" onclick="toggleZemtabTheme()" class="rounded-full border border-zem-border bg-zem-card px-3 py-2 text-sm font-bold text-zem-muted" aria-label="{{ __('Switch color theme') }}"><span class="dark:hidden">{{ __('Dark') }}</span><span class="hidden dark:inline">{{ __('Light') }}</span></button>
                @endunless
                <div class="rounded-full border border-zem-border bg-zem-card px-4 py-2 text-sm text-zem-muted">{{ auth()->user()->name }}</div>
            </div>
        </header>
        @if(session('success'))<div class="mb-5 rounded-lg border border-zem-green/40 bg-zem-green/15 px-4 py-3 text-sm text-zem-cream">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="mb-5 rounded-lg border border-red-500/40 bg-red-950/60 px-4 py-3 text-sm">{{ $errors->first() }}</div>@endif
        @php($showWarning = isset($dashboardRestaurant) && $dashboardRestaurant && $dashboardRestaurant->isExpiringSoon() && !request()->is('admin/*'))
        @if($showWarning)
            <div class="mb-5 rounded-lg border border-zem-gold/40 bg-zem-gold/10 px-4 py-3 text-sm">
                <p class="font-bold text-zem-gold">{{ __('Subscription expiring soon') }}</p>
                <p class="mt-1 text-zem-muted">Your subscription expires in {{ $dashboardRestaurant->daysUntilExpiry() }} day(s). Please pay to keep your dashboard active.</p>
                <p class="mt-2 text-zem-muted">Pay via Telebirr, CBE, Awash Bank, or Bank of Abyssinia. Send your payment screenshot with your restaurant name to Telegram: <strong class="text-zem-cream">{{ env('PAYMENT_TELEGRAM', '@Zemtab') }}</strong></p>
            </div>
        @endif
        @yield('content')
    </main>
</div>
@unless($isAdmin)<script>
function toggleZemtabTheme() {
    const dark = !document.documentElement.classList.contains('dark');
    document.documentElement.classList.toggle('dark', dark);
    localStorage.setItem('zemtabTheme', dark ? 'dark' : 'light');
}
</script>@endunless
</body>
</html>
