@php
    $isAdmin = request()->is('admin/*');
    $dashboardRestaurant = $isAdmin ? null : auth()->user()?->restaurant;
    $placePlural = $dashboardRestaurant?->locationLabelTitle(true) ?? 'Tables';
    $dashboardLogoUrl = $dashboardRestaurant?->logo_path
        ? (\Illuminate\Support\Str::startsWith($dashboardRestaurant->logo_path, ['http://', 'https://', 'uploads/'])
            ? (str_starts_with($dashboardRestaurant->logo_path, 'uploads/') ? asset($dashboardRestaurant->logo_path) : $dashboardRestaurant->logo_path)
            : asset('storage/'.$dashboardRestaurant->logo_path))
        : null;
    if (! $dashboardLogoUrl && $dashboardRestaurant?->slug === 'ginashotel') {
        $dashboardLogoUrl = asset('uploads/restaurants/ginas-hotel-logo.svg');
    }
    $zemtabBrandBadge = $dashboardRestaurant?->zemtabBrandBadge();

    $staffRole = session('staff_profile_role', 'owner_manager');
    $profileName = session('staff_profile_name', 'Owner/Manager');
    $accountLabel = $isAdmin ? 'Admin' : ($dashboardRestaurant?->name ?? 'Restaurant') . ' - ' . ($staffRole === 'owner_manager' ? 'Owner/Manager' : ($staffRole === 'cashier' ? 'Cashier' : 'Kitchen'));

    $links = $isAdmin
        ? [
            ['Overview', route('admin.dashboard')],
            ['Restaurants & Hotels', route('admin.restaurants.index')],
            ['Users', route('admin.users.index')],
            ['Demo Requests', route('admin.demo-requests.index')],
            ['Subscriptions', route('admin.subscriptions.index')],
            ['Payments', route('admin.payments.index')],
            ['Payment Settings', route('admin.payment-settings.index')],
            ['Database', route('admin.database')],
        ]
        : ($staffRole === 'cashier'
            ? [
                [__('Work Board'), route('restaurant.orders.index')],
            ]
            : ($staffRole === 'kitchen'
                ? [
                    [__('Work Board'), route('restaurant.orders.index')],
                ]
                : [
                    [__('Overview'), route('restaurant.dashboard')],
                    [__('Analytics'), route('restaurant.analytics')],
                    [__('Work Board'), route('restaurant.orders.index')],
                    [__('Cashier Reports'), route('restaurant.cashier-reports')],
                    [__('Menu Items'), route('restaurant.menu-items.index')],
                    [__('Categories'), route('restaurant.categories.index')],
                    [$placePlural.' / QR', route('restaurant.tables.index')],
                    [__('Staff Profiles'), route('restaurant.staff-profiles.index')],
                    [__('Settings'), route('restaurant.settings.edit')],
                ]
            )
        );
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    <title>{{ $title ?? ($heading ?? 'Dashboard').' · ZemTab' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo/zemtab-pantone-1795-c-icon-transparent.png') }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <script>
        (() => {
            let saved;
            try { saved = localStorage.getItem('zemtabTheme'); } catch (_) {}
            const dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    @include('components.frontend-assets', ['alpine' => true])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Ethiopic:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--zem-bg:248 250 252;--zem-card:255 255 255;--zem-text:0 0 0;--zem-muted:71 84 103;--zem-border:216 224 231;--zem-soft:238 243 247;color-scheme:light}
        .dark{--zem-bg:15 17 21;--zem-card:24 27 34;--zem-text:245 247 250;--zem-muted:167 176 190;--zem-border:48 53 65;--zem-soft:32 36 45;color-scheme:dark}
        .dark .bg-white{background-color:rgb(var(--zem-card))!important}.dark .bg-neutral-50,.dark .bg-neutral-100{background-color:rgb(var(--zem-soft))!important}.dark .text-black,.dark .text-neutral-700{color:rgb(var(--zem-text))!important}.dark .text-neutral-500,.dark .text-neutral-600{color:rgb(var(--zem-muted))!important}
        [x-cloak]{display:none!important}
        input,select,textarea,button{font-size:16px}
        input:not([type="color"]):not([type="checkbox"]):not([type="radio"]),select,textarea{background-color:rgb(var(--zem-card));color:rgb(var(--zem-text))}
        @keyframes slide-in{from{opacity:0;transform:translateY(-12px)}to{opacity:1;transform:translateY(0)}}
        .animate-slide-in{animation:slide-in .4s ease-out}
        :focus-visible{outline:3px solid #D22630;outline-offset:3px}
        .dashboard-nav a[aria-current="page"]{background:rgb(var(--zem-soft));color:rgb(var(--zem-text));box-shadow:inset 3px 0 #D22630}
        .dashboard-nav a{min-height:42px;display:flex;align-items:center}
        .dashboard-main section{border-radius:14px}
        .dashboard-main h1{letter-spacing:-.035em}
        .dashboard-main h2{letter-spacing:-.02em}
        .dashboard-main article[data-order-id]{border-radius:14px}
        .metric-value{font-variant-numeric:tabular-nums;letter-spacing:-.04em}
        @media(prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;scroll-behavior:auto!important;transition:none!important}}
    </style>
</head>
<body class="bg-zem-bg text-zem-cream font-sans antialiased">
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:bg-zem-card focus:p-3">{{ __('Skip to content') }}</a>
<div class="min-h-screen bg-zem-bg lg:flex" x-data="{ navigationOpen: false }">
    <aside class="border-b border-zem-border bg-zem-card lg:fixed lg:inset-y-0 lg:z-30 lg:flex lg:w-60 lg:flex-col lg:border-b-0 lg:border-r">
        <div class="flex items-center justify-between px-5 py-5">
            <a href="{{ route('home') }}" class="relative inline-flex items-center pb-3 pr-8" aria-label="ZemTab Home">
                <img src="{{ asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}" alt="ZemTab" class="h-9 w-auto dark:rounded-md dark:bg-white dark:p-1">
                @if($zemtabBrandBadge)
                    <span class="absolute bottom-0 right-0 rounded-full border border-zem-border bg-zem-card px-2 py-0.5 text-[.62rem] font-extrabold leading-none text-zem-gold shadow-sm">{{ __($zemtabBrandBadge) }}</span>
                @endif
            </a>
            <button type="button" @click="navigationOpen = !navigationOpen" :aria-expanded="navigationOpen" aria-controls="dashboard-navigation" class="rounded-lg border border-zem-border px-3 py-2 text-sm font-semibold lg:hidden">{{ __('Menu') }}</button>
        </div>
        <div class="hidden px-5 pb-5 lg:block">
            <p class="truncate text-sm font-bold">{{ $isAdmin ? 'Platform administration' : $dashboardRestaurant?->name }}</p>
            <p class="mt-1 text-xs text-zem-muted">{{ $isAdmin ? auth()->user()->name : $profileName }}</p>
        </div>
        <nav id="dashboard-navigation" aria-label="{{ __('Main navigation') }}" :class="navigationOpen ? 'block' : 'hidden'" class="dashboard-nav hidden space-y-1 overflow-y-auto px-3 pb-4 lg:!block lg:flex-1">
            @foreach($links as [$label, $url])
                @if(($isAdmin && $loop->index === 4) || (!$isAdmin && $staffRole === 'owner_manager' && $loop->index === 4))
                    <p class="px-3 pb-2 pt-5 text-[11px] font-semibold uppercase tracking-widest text-zem-muted">{{ $isAdmin ? 'Billing & settings' : __('Manage your space') }}</p>
                @endif
                <a href="{{ $url }}" @if(url()->current() === $url) aria-current="page" @endif class="rounded-lg px-3 py-2 text-sm font-medium text-zem-muted transition hover:bg-zem-soft hover:text-zem-cream">{{ $label }}</a>
            @endforeach
        </nav>
        <div :class="navigationOpen ? 'block' : 'hidden'" class="hidden border-t border-zem-border p-4 lg:!block">
            @unless($isAdmin)
                <a href="{{ route('restaurant.profile-select') }}" class="mb-2 block rounded-lg px-3 py-2 text-sm font-semibold text-zem-muted hover:bg-zem-soft">{{ __('Switch profile') }}</a>
            @endunless
            <form method="post" action="{{ route('logout') }}">@csrf<button class="w-full rounded-lg px-3 py-2 text-left text-sm text-zem-muted hover:bg-zem-soft">{{ __('Logout') }}</button></form>
        </div>
    </aside>
    <main id="main-content" class="dashboard-main min-w-0 w-full px-4 py-6 md:px-6 lg:ml-60 lg:px-8">
        <header class="mb-7 flex flex-wrap items-center justify-between gap-4">
            <div class="min-w-0">
                <p class="mb-1 text-xs font-medium text-zem-muted">{{ $eyebrow ?? ($isAdmin ? 'ZemTab' : $dashboardRestaurant?->name) }}</p>
                <h1 class="font-display text-2xl font-bold md:text-3xl">{{ $heading ?? 'Dashboard' }}</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @unless($isAdmin)
                    <form method="post" action="{{ route('locale.update') }}">@csrf<input type="hidden" name="locale" value="{{ app()->getLocale() === 'am' ? 'en' : 'am' }}"><button class="rounded-full border border-zem-border bg-zem-card px-3 py-2 text-sm font-bold text-zem-muted">{{ app()->getLocale() === 'am' ? 'English' : 'Amharic' }}</button></form>
                @endunless
                <button type="button" onclick="toggleZemtabTheme()" class="rounded-full border border-zem-border bg-zem-card px-3 py-2 text-sm font-bold text-zem-muted" aria-label="{{ __('Switch color theme') }}"><span class="dark:hidden">{{ __('Dark') }}</span><span class="hidden dark:inline">{{ __('Light') }}</span></button>
                <span class="hidden text-xs text-zem-muted xl:inline">{{ $isAdmin ? 'Admin' : ($staffRole === 'owner_manager' ? __('Owner/Manager') : ucfirst($staffRole)) }}</span>
            </div>
        </header>
        @if(session('success'))<div role="status" class="mb-5 rounded-lg border border-zem-green/40 bg-zem-green/10 px-4 py-3 text-sm text-zem-cream">{{ session('success') }}</div>@endif
        @if($errors->any())<div role="alert" class="mb-5 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-zem-cream">{{ $errors->first() }}</div>@endif
        @php($showWarning = $dashboardRestaurant && $staffRole === 'owner_manager' && $dashboardRestaurant->isExpiringSoon())
        @if($showWarning)
            <div class="mb-5 rounded-lg border border-zem-gold/40 bg-zem-gold/10 px-4 py-3 text-sm">
                <p class="font-bold text-zem-gold">{{ __('Subscription expiring soon') }}</p>
                <p class="mt-1 text-zem-muted">Your subscription expires in {{ $dashboardRestaurant->daysUntilExpiry() }} day(s). Please pay to keep your dashboard active.</p>
                <details class="mt-3"><summary class="cursor-pointer font-semibold">{{ __('Payment details') }}</summary>
                <div class="mt-3 grid gap-2 rounded-md border border-zem-gold/30 bg-zem-card/60 p-3 text-zem-muted sm:grid-cols-2">
                    <p>Telebirr: <strong class="text-zem-cream">{{ config('payment.telebirr') }}</strong></p>
                    <p>CBE: <strong class="text-zem-cream">{{ config('payment.cbe') }}</strong></p>
                    <p>Awash Bank: <strong class="text-zem-cream">{{ config('payment.awash') }}</strong></p>
                    <p>Bank of Abyssinia: <strong class="text-zem-cream">{{ config('payment.abyssinia') }}</strong></p>
                </div>
                <p class="mt-3 text-zem-muted">After paying, send your payment screenshot with your restaurant name to Telegram: <strong class="text-zem-cream">{{ config('payment.telegram') }}</strong></p>
                </details>
            </div>
        @endif
        @yield('content')
    </main>
</div>
<script>
function toggleZemtabTheme() {
    const dark = !document.documentElement.classList.contains('dark');
    document.documentElement.classList.toggle('dark', dark);
    try { localStorage.setItem('zemtabTheme', dark ? 'dark' : 'light'); } catch (_) {}
}
</script>
</body>
</html>
