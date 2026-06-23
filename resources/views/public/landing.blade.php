@extends('layouts.app', [
    'title' => 'ZemTab - QR Menu, Table & Room Ordering for Restaurants and Hotels in Ethiopia',
    'description' => 'ZemTab is a German-made, Ethiopia-based QR menu, table ordering, and hotel room ordering system. Guests scan, order, request service, and pay from their phone. No app download needed.',
    'keywords' => 'QR menu Ethiopia, restaurant ordering Addis Ababa, hotel room ordering, digital menu, table ordering, room service QR, staff call, bill request, restaurant POS, hotel service system, ZemTab',
    'canonical' => url('/'),
    'ogType' => 'website',
    'ogImage' => asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png'),
])

@section('content')
<main class="scroll-smooth overflow-hidden bg-zem-bg text-zem-cream" itemscope itemtype="https://schema.org/SoftwareApplication">
    <meta itemprop="name" content="ZemTab">
    <meta itemprop="applicationCategory" content="BusinessApplication">
    <meta itemprop="operatingSystem" content="Any (Web-based)">
    <meta itemprop="softwareVersion" content="1.0">
    <meta itemprop="inLanguage" content="{{ app()->getLocale() }}">
    <meta itemprop="countriesSupported" content="ET">

    {{-- Sticky Header --}}
    <header class="fixed inset-x-0 top-0 z-40 border-b border-zem-border bg-white/90 shadow-sm backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4">
            <a href="/" class="inline-flex items-center" aria-label="ZemTab Home">
                <img src="{{ asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}" alt="ZemTab Logo - QR Menu, Table and Room Ordering System" class="h-12 w-auto">
            </a>
            <nav class="hidden items-center gap-6 text-sm font-semibold text-zem-muted md:flex" aria-label="Primary navigation">
                <a class="transition hover:text-zem-gold" href="#workflow">{{ __('Workflow') }}</a>
                <a class="transition hover:text-zem-gold" href="#features">{{ __('Features') }}</a>
                <a class="transition hover:text-zem-gold" href="#pricing">{{ __('Pricing') }}</a>
                <a class="transition hover:text-zem-gold" href="#faq">FAQ</a>
                <a class="transition hover:text-zem-gold" href="#demo">{{ __('Demo') }}</a>
            </nav>
            <div class="flex items-center gap-3 md:hidden">
                <form method="post" action="{{ route('locale.update') }}">@csrf<input type="hidden" name="locale" value="{{ app()->getLocale() === 'am' ? 'en' : 'am' }}"><button class="rounded-lg border border-zem-border px-2 py-2 text-xs font-extrabold">{{ app()->getLocale() === 'am' ? 'EN' : 'አማ' }}</button></form>
                <a href="#demo" class="rounded-lg bg-zem-gold px-3 py-2 text-xs font-extrabold text-white">{{ __('Demo') }}</a>
                <button id="mobile-menu-toggle" class="rounded-lg border border-zem-border p-2 text-zem-cream" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
            <div class="hidden items-center gap-3 md:flex">
                <form method="post" action="{{ route('locale.update') }}">@csrf<input type="hidden" name="locale" value="{{ app()->getLocale() === 'am' ? 'en' : 'am' }}"><button class="rounded-lg border border-zem-border px-3 py-2 text-sm font-extrabold text-zem-muted">{{ app()->getLocale() === 'am' ? 'English' : 'አማርኛ' }}</button></form>
                <a href="{{ route('login') }}" class="rounded-lg border border-zem-border px-4 py-2 text-sm font-extrabold text-zem-cream transition hover:border-zem-gold hover:bg-zem-gold/10">{{ __('Login') }}</a>
                <a href="#demo" class="rounded-lg bg-zem-charcoal px-4 py-2 text-sm font-extrabold text-white transition hover:bg-zem-gold">{{ __('Request Demo') }}</a>
            </div>
        </div>
        {{-- Mobile Menu Drawer --}}
        <div id="mobile-menu" class="hidden border-t border-zem-border bg-white/95 px-5 py-4 shadow-lg md:hidden">
            <nav class="flex flex-col gap-3 text-sm font-semibold text-zem-muted" aria-label="Mobile navigation">
                <a class="transition hover:text-zem-gold" href="#workflow">{{ __('Workflow') }}</a>
                <a class="transition hover:text-zem-gold" href="#features">{{ __('Features') }}</a>
                <a class="transition hover:text-zem-gold" href="#pricing">{{ __('Pricing') }}</a>
                <a class="transition hover:text-zem-gold" href="#faq">FAQ</a>
                <a class="transition hover:text-zem-gold" href="#demo">{{ __('Request Demo') }}</a>
                <a class="transition hover:text-zem-gold" href="{{ route('login') }}">{{ __('Login') }}</a>
            </nav>
        </div>
    </header>

    {{-- Floating Telegram CTA --}}
    <a
        href="https://t.me/{{ ltrim(config('payment.telegram', '@Zemtab'), '@') }}"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Chat with ZemTab on Telegram"
        class="fixed bottom-5 left-5 z-50 inline-flex h-14 w-14 items-center justify-center rounded-full bg-[#229ED9] text-white shadow-xl shadow-sky-600/25 ring-1 ring-white/70 transition hover:-translate-y-0.5 hover:bg-[#1f91c8] focus:outline-none focus:ring-4 focus:ring-[#229ED9]/30 sm:h-auto sm:w-auto sm:gap-2 sm:rounded-lg sm:px-4 sm:py-3"
    >
        <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M21.94 4.11 18.7 19.39c-.24 1.08-.88 1.34-1.78.83l-4.94-3.64-2.38 2.29c-.27.27-.49.49-1 .49l.35-5.03 9.16-8.27c.4-.35-.09-.55-.62-.2L6.17 12.98l-4.88-1.52c-1.06-.33-1.08-1.06.22-1.57L20.6 2.54c.88-.33 1.66.21 1.34 1.57z"/>
        </svg>
        <span class="hidden text-sm font-extrabold sm:inline">{{ __('Chat on Telegram') }}</span>
    </a>

    {{-- Hero Section --}}
    <section class="relative px-5 pt-24 sm:pt-28" aria-label="Hero">
        <div class="absolute inset-0 bg-[linear-gradient(135deg,#F8FAFC_0%,#FFFFFF_44%,#EEF3F7_100%)]"></div>
        <div class="absolute inset-y-0 right-0 hidden w-[68%] lg:block">
            <img
                src="{{ asset('uploads/zemtab-right-hand-cafe-hero-optimized.webp') }}"
                alt=""
                aria-hidden="true"
                class="h-full w-full translate-y-12 object-cover object-[72%_42%] opacity-95 [mask-image:linear-gradient(90deg,transparent_0%,rgba(0,0,0,.12)_12%,rgba(0,0,0,.76)_32%,black_54%,black_82%,rgba(0,0,0,.5)_92%,transparent_100%),linear-gradient(180deg,transparent_0%,rgba(0,0,0,.65)_10%,black_22%,black_74%,rgba(0,0,0,.58)_86%,transparent_100%)] [mask-composite:intersect] [-webkit-mask-image:linear-gradient(90deg,transparent_0%,rgba(0,0,0,.12)_12%,rgba(0,0,0,.76)_32%,black_54%,black_82%,rgba(0,0,0,.5)_92%,transparent_100%),linear-gradient(180deg,transparent_0%,rgba(0,0,0,.65)_10%,black_22%,black_74%,rgba(0,0,0,.58)_86%,transparent_100%)] [-webkit-mask-composite:source-in]"
            >
        </div>
        <div class="absolute inset-y-0 right-0 hidden w-[70%] bg-[linear-gradient(90deg,#F8FAFC_0%,rgba(248,250,252,.88)_16%,rgba(248,250,252,.38)_34%,transparent_58%),linear-gradient(180deg,transparent_0%,transparent_68%,#F8FAFC_100%)] lg:block"></div>
        <div class="absolute inset-x-0 top-0 h-36 bg-gradient-to-b from-white to-transparent"></div>
        <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-zem-bg to-transparent"></div>
        <div class="relative mx-auto grid min-h-[88vh] max-w-7xl items-center gap-8 pb-24 lg:grid-cols-[.92fr_1.08fr] lg:gap-10 lg:pb-20">
            <div class="max-w-3xl">
                <p class="mb-4 inline-flex rounded-full border border-zem-gold/30 bg-zem-gold/10 px-4 py-2 text-[.68rem] font-extrabold uppercase tracking-[.22em] text-zem-gold sm:mb-5 sm:text-xs sm:tracking-[.26em]">{{ __('Scan. Order. Pay.') }}</p>
                <img src="{{ asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}" alt="ZemTab - Digital Menu, Table Ordering and Hotel Room Ordering" class="-ml-2 h-auto w-full max-w-[18.5rem] sm:-ml-4 sm:max-w-xl">
                <h1 class="mt-4 font-display text-2xl font-extrabold leading-tight sm:text-3xl md:text-4xl">{{ __('QR Menu, Table & Room Ordering') }}</h1>
                <p class="sr-only">ZemTab - Modern QR Menu, Table Ordering and Hotel Room Ordering System in Ethiopia</p>
                <p class="mt-3 text-sm font-bold text-zem-muted">{{ __('German-made · Ethiopia-based') }}</p>
                <p class="mt-5 max-w-2xl text-base leading-7 text-zem-muted sm:text-xl sm:leading-8">{{ __('A QR ordering system for restaurants, cafes, lounges, and hotels. Guests scan from a table or room, order from their phone, request service, and pay at the end.') }}</p>
                <div class="mt-6 grid grid-cols-2 gap-3 sm:mt-9 sm:flex sm:flex-wrap">
                    <a href="#demo" class="rounded-lg bg-zem-gold px-4 py-3 text-center text-sm font-extrabold text-white shadow-xl shadow-zem-gold/20 transition hover:bg-zem-redDark sm:px-6 sm:text-base">{{ __('Request Demo') }}</a>
                    <a href="{{ route('login') }}" class="rounded-lg border border-zem-gold bg-zem-gold/10 px-4 py-3 text-center text-sm font-extrabold text-zem-gold transition hover:bg-zem-gold hover:text-white sm:px-6 sm:text-base">{{ __('Login') }}</a>
                    <a href="#features" class="col-span-2 rounded-lg border border-zem-border bg-white px-4 py-3 text-center text-sm font-extrabold text-zem-cream transition hover:border-zem-gold hover:bg-zem-gold/10 sm:col-span-1 sm:px-6 sm:text-base">{{ __('See Features') }}</a>
                </div>
                {{-- Trust row --}}
                <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-zem-muted">
                    <span class="inline-flex items-center gap-1.5 font-semibold text-zem-cream">
                        <svg class="h-4 w-4 text-zem-gold" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.36 4.18a1 1 0 00.95.69h4.4c.97 0 1.37 1.24.59 1.81l-3.56 2.59a1 1 0 00-.36 1.12l1.36 4.18c.3.92-.76 1.68-1.54 1.12l-3.56-2.59a1 1 0 00-1.18 0l-3.56 2.59c-.78.56-1.84-.2-1.54-1.12l1.36-4.18a1 1 0 00-.36-1.12L2.4 9.61c-.78-.57-.38-1.81.59-1.81h4.4a1 1 0 00.95-.69z"/></svg>
                        4.8/5
                    </span>
                    <span class="h-4 w-px bg-zem-border"></span>
                    <span>{{ __('No app download needed') }}</span>
                    <span class="hidden h-4 w-px bg-zem-border sm:block"></span>
                    <span class="hidden sm:inline">{{ __('No commission on orders') }}</span>
                </div>
                <div class="relative -mx-5 mt-6 h-[29rem] overflow-hidden bg-white sm:mx-0 sm:h-[34rem] lg:hidden" aria-label="ZemTab QR menu and table ordering shown clearly on a phone held in a guest's right hand in a cafe">
                    <img
                        src="{{ asset('uploads/zemtab-right-hand-cafe-hero-optimized.webp') }}"
                        alt="ZemTab QR menu and table ordering shown clearly on a phone held in a guest's right hand in a cafe"
                        class="absolute inset-0 h-full w-full object-cover object-[72%_50%] [mask-image:linear-gradient(180deg,black_0%,black_82%,transparent_100%)] [-webkit-mask-image:linear-gradient(180deg,black_0%,black_82%,transparent_100%)]"
                    >
                </div>
                <div class="mt-8 grid max-w-2xl grid-cols-3 gap-3 sm:mt-12">
                    @foreach([['30s','guest ordering flow'],['24/7','menu and room service access'],['1 scan','to start ordering']] as $metric)
                        <div class="border-l border-zem-gold pl-4">
                            <p class="font-display text-2xl font-extrabold sm:text-3xl">{{ $metric[0] }}</p>
                            <p class="text-xs leading-4 text-zem-muted sm:text-sm sm:leading-normal">{{ __($metric[1]) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="relative hidden min-h-[42rem] lg:block" aria-hidden="true"></div>
        </div>
    </section>

    {{-- Workflow Section --}}
    <section id="workflow" class="scroll-mt-24 border-y border-zem-border bg-zem-soft py-12 sm:py-16">
        <div class="mx-auto max-w-7xl px-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between">
                <h2 class="font-display text-2xl font-extrabold sm:text-3xl md:text-4xl">{{ __('From scan to service in one flow') }}</h2>
                <p class="max-w-xl text-sm leading-6 text-zem-muted sm:text-base sm:leading-7">{{ __('Guests scan, order, request service, and pay. Staff manage everything live.') }}</p>
            </div>
            <div class="mt-6 grid grid-cols-1 gap-3 sm:mt-8 sm:grid-cols-2 sm:gap-4 md:grid-cols-5">
                @foreach([['Scan','Open the menu from a table or room QR.'],['Browse','View available items and prices.'],['Order','Choose items and add a note if needed.'],['Serve','Staff receive orders and requests live.'],['Pay','Pay at the end using the available method.']] as $step)
                    <article class="rounded-xl border border-zem-border bg-white p-5 transition hover:-translate-y-1 hover:border-zem-gold/50">
                        <p class="text-sm font-extrabold text-zem-gold">0{{ $loop->iteration }}</p>
                        <h3 class="mt-4 font-display text-xl font-bold">{{ __($step[0]) }}</h3>
                        <p class="mt-3 text-sm leading-6 text-zem-muted">{{ __($step[1]) }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Features + Ideal For Section --}}
    <section id="features" class="scroll-mt-24 mx-auto max-w-7xl px-5 py-12 sm:py-16">
        <div class="max-w-3xl">
            <h2 class="font-display text-2xl font-extrabold sm:text-3xl md:text-4xl">{{ __('Built for smoother daily operations') }}</h2>
            <p class="mt-3 text-sm leading-6 text-zem-muted sm:mt-4 sm:text-base sm:leading-7">{{ __('Keep menus current, requests visible, and every service point organized.') }}</p>
        </div>
        <div class="mt-6 grid grid-cols-1 gap-4 sm:mt-8 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4">
            @foreach([['Update instantly','Change menu items, prices, and details anytime.'],['Control availability','Hide sold-out items without reprinting a menu.'],['One live dashboard','See orders and service requests in one place.'],['Tables and rooms','Give every table or room its own QR service point.']] as $feature)
                <article class="rounded-xl border border-zem-border bg-white p-6 transition hover:border-zem-gold/50 hover:bg-zem-gold/10">
                    <h3 class="font-display text-xl font-extrabold">{{ __($feature[0]) }}</h3>
                    <p class="mt-3 leading-6 text-zem-muted">{{ __($feature[1]) }}</p>
                </article>
            @endforeach
        </div>

        {{-- Ideal For (as icon cards) --}}
        <div class="mt-8 border-t border-zem-border pt-8 sm:mt-12 sm:pt-12">
            <h3 class="text-center font-display text-lg font-extrabold sm:text-2xl md:text-3xl">{{ __('Ideal for') }}</h3>
            <div class="mt-6 grid grid-cols-2 gap-3 sm:mt-8 sm:grid-cols-4 sm:gap-4">
                @foreach([
                    ['Restaurants','Full-service dining with live table orders.','M18 3l4 4-4 4M6 3L2 7l4 4','15 21v-2a4 4 0 00-4-4H4a4 4 0 00-4 4v2'],
                    ['Cafes','Quick menu updates and call-staff requests.','M3 8h18l-1 9a2 2 0 01-2 2H6a2 2 0 01-2-2L3 8zM5 8a7 7 0 0114 0','8 4v4M12 2v2'],
                    ['Hotels','Room service QR points and bill requests.','M3 21V7l9-4 9 4v14M9 21v-6h6v6','9 11h6'],
                    ['Lounges','Service requests and orders in one flow.','M5 3h14v14H5zM5 17l-2 4M19 17l2 4M9 7h6v6H9z'],
                ] as $who)
                    <article class="flex flex-col items-center rounded-xl border border-zem-border bg-white p-5 text-center transition hover:-translate-y-1 hover:border-zem-gold/50">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-zem-gold/10 text-zem-gold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $who[2] }}"/></svg>
                        </span>
                        <h4 class="mt-3 font-display text-lg font-bold">{{ __($who[0]) }}</h4>
                        <p class="mt-1 text-xs leading-5 text-zem-muted">{{ __($who[1]) }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Pricing Section --}}
    <section id="pricing" class="scroll-mt-24 border-y border-zem-border bg-zem-soft py-16" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
        <div class="mx-auto max-w-3xl px-5 text-center">
            <h2 class="font-display text-3xl font-extrabold md:text-4xl">{{ __('Simple monthly pricing') }}</h2>
            <p class="mt-4 text-zem-muted">{{ __('Everything needed to run QR menus, table or room orders, service requests, and a live staff dashboard. No commission on orders.') }}</p>
        </div>
        <div class="mx-auto mt-8 max-w-2xl px-5">
            <div class="relative rounded-2xl border border-zem-gold bg-white p-7 shadow-2xl shadow-zem-gold/10">
                <span class="absolute right-6 top-6 rounded-full bg-zem-gold px-3 py-1 text-[.7rem] font-extrabold uppercase tracking-wider text-white">{{ __('All-in-one') }}</span>
                <p class="text-sm font-extrabold uppercase tracking-[.22em] text-zem-gold">{{ __('Monthly plan') }}</p>
                <h3 class="mt-3 font-display text-5xl font-extrabold text-zem-cream" itemprop="price" content="2000">{{ __('2,000 birr') }}</h3>
                <p class="mt-2 text-zem-muted" itemprop="priceCurrency" content="ETB">{{ __('per month · no commission on orders') }}</p>
                <div class="mt-5 rounded-xl border border-zem-border bg-white p-4">
                    <p class="font-bold text-zem-cream">{{ __('First-time setup: 12,000 birr') }}</p>
                    <p class="mt-2 text-sm leading-6 text-zem-muted">{{ __('We handle the QR printouts and set up everything system-wise in your restaurant or hotel workspace so your team can start using ZemTab smoothly.') }}</p>
                </div>
                <div class="mt-6 grid gap-3 text-sm font-semibold text-zem-cream sm:grid-cols-2">
                    @foreach(['Digital QR menu','Table and room ordering','Call staff and bill requests','Live staff dashboard','Menu availability control','QR printout setup'] as $item)
                        <p class="flex items-center gap-2 rounded-lg border border-zem-border bg-white px-4 py-3">
                            <svg class="h-4 w-4 shrink-0 text-zem-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            {{ __($item) }}
                        </p>
                    @endforeach
                </div>
                <a href="#demo" class="mt-7 inline-flex rounded-lg bg-zem-gold px-6 py-3 font-extrabold text-white transition hover:bg-zem-redDark">{{ __('Request Demo') }}</a>
            </div>
        </div>
    </section>

    {{-- FAQ Section --}}
    <section id="faq" class="scroll-mt-24 mx-auto max-w-3xl px-5 py-16">
        <h2 class="font-display text-3xl font-extrabold md:text-4xl">{{ __('Frequently asked questions') }}</h2>
        <p class="mt-4 text-zem-muted">{{ __('Everything you need to know before getting started with ZemTab.') }}</p>
        <div class="mt-8 space-y-6" x-data="{ open: null }">
            @php
                $faqGroups = [
                    'Getting started' => [
                        ['Do guests need to download an app?','No. Guests simply scan the QR code on their table or in their room with their phone camera and the menu opens instantly in their browser. No app installs, no sign-ups.'],
                        ['How do I set up QR codes for tables or rooms?','ZemTab generates a unique QR code for each table or hotel room. You can download and print them directly from the dashboard, or we can help with setup.'],
                    ],
                    'Payments & pricing' => [
                        ['What payment methods are supported?','Guests can pay at the end with cash, Telebirr, CBE, bank transfer, or other manual methods. Restaurants and hotels can show payment QR codes and account numbers, then guests upload or show proof to staff.'],
                        ['How much does it cost?','ZemTab is 2,000 birr per month, plus a one-time 12,000 birr setup charge. The setup charge covers QR printouts and full system setup inside your restaurant or hotel workspace. No commission on orders.'],
                    ],
                    'Locations' => [
                        ['Can I update menu prices in real-time?','Yes. Any change you make in the dashboard - prices, item names, descriptions, availability - reflects instantly on the QR menu guests see.'],
                        ['What if I have multiple locations?','Each location runs as its own ZemTab subscription with its own menus, tables or rooms, and staff. This keeps billing and setup simple per venue.'],
                    ],
                ];
                $globalIndex = 0;
            @endphp
            @foreach($faqGroups as $groupTitle => $questions)
                <div>
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-extrabold uppercase tracking-[.18em] text-zem-gold">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5h14v14H5z"/></svg>
                        {{ __($groupTitle) }}
                    </h3>
                    <div class="space-y-3">
                        @foreach($questions as $faq)
                            <div class="rounded-xl border border-zem-border bg-white">
                                <button
                                    @click="open === {{ $globalIndex }} ? open = null : open = {{ $globalIndex }}"
                                    class="flex w-full items-center justify-between px-5 py-4 text-left font-semibold text-zem-cream transition hover:text-zem-gold"
                                    :aria-expanded="open === {{ $globalIndex }}"
                                    aria-controls="faq-{{ $globalIndex }}"
                                >
                                    <span>{{ __($faq[0]) }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 transition-transform" :class="open === {{ $globalIndex }} ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div id="faq-{{ $globalIndex }}" x-show="open === {{ $globalIndex }}" x-collapse class="border-t border-zem-border px-5 py-4 text-sm leading-7 text-zem-muted">
                                    {{ __($faq[1]) }}
                                </div>
                            </div>
                            @php $globalIndex++; @endphp
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Demo Request Section --}}
    <section id="demo" class="scroll-mt-24 border-t border-zem-border bg-zem-soft py-16">
        <div class="mx-auto grid max-w-7xl gap-8 px-5 lg:grid-cols-[.8fr_1.2fr]">
            <div>
                <h2 class="font-display text-3xl font-extrabold md:text-4xl">{{ __('Request a demo') }}</h2>
                <p class="mt-3 leading-7 text-zem-muted">{{ __('Tell us about your restaurant or hotel, table or room count, and service style. ZemTab can be set up for a single cafe, a full restaurant, or a hotel. Each location runs as its own subscription.') }}</p>
                <div class="mt-6 space-y-3 text-sm">
                    <div class="inline-flex items-center gap-2 rounded-full border border-zem-green/40 bg-zem-green/10 px-3 py-1 text-zem-green">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ __('Full setup available') }}
                    </div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-zem-green/40 bg-zem-green/10 px-3 py-1 text-zem-green">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ __('No credit card required') }}
                    </div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-zem-green/40 bg-zem-green/10 px-3 py-1 text-zem-green">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ __('Cancel anytime') }}
                    </div>
                </div>
                <address class="mt-6 grid gap-4 not-italic text-sm text-zem-muted sm:grid-cols-2 lg:grid-cols-1">
                    <div>
                        <p class="font-bold text-zem-cream">{{ __('Contact') }}</p>
                        <p class="mt-2"><a href="mailto:zemtab.support@gmail.com" class="hover:text-zem-gold">zemtab.support@gmail.com</a></p>
                        <p><a href="https://zemtab.com" class="hover:text-zem-gold">zemtab.com</a></p>
                        <p class="mt-2"><a href="tel:+251974217074" class="hover:text-zem-gold">ET +251 974 217 074</a></p>
                        <p><a href="tel:+4916092988456" class="hover:text-zem-gold">DE +49 160 92988456</a></p>
                    </div>
                    <div>
                        <p><strong class="text-zem-cream">Ethiopia:</strong><br>Gabon Street, Woreda 02<br>Addis Ababa, Ethiopia</p>
                        <p class="mt-3"><strong class="text-zem-cream">Germany:</strong><br>Stuttgart, Baden-Württemberg<br>Germany</p>
                    </div>
                </address>
            </div>
            <form method="post" action="{{ route('demo-requests.store') }}" class="grid gap-5 rounded-xl border border-zem-border bg-white p-6">
                @csrf
                <div>
                    <h3 class="font-display text-xl font-bold text-zem-cream">{{ __('Tell us about your venue') }}</h3>
                    <p class="mt-1 text-sm text-zem-muted">{{ __("We'll get back to you within one business day.") }}</p>
                </div>
                <fieldset class="grid gap-4 md:grid-cols-2">
                    <legend class="mb-3 w-full border-b border-zem-border pb-2 text-xs font-extrabold uppercase tracking-wider text-zem-muted">{{ __('Contact') }}</legend>
                    <input name="name" required placeholder="{{ __('Name') }}" class="rounded-lg border border-zem-border bg-white px-4 py-3 outline-none focus:border-zem-gold" aria-label="{{ __('Your name') }}">
                    <input name="phone" required placeholder="{{ __('Phone number') }}" class="rounded-lg border border-zem-border bg-white px-4 py-3 outline-none focus:border-zem-gold" aria-label="{{ __('Phone number') }}">
                    <input name="email" type="email" placeholder="{{ __('Email (optional)') }}" class="rounded-lg border border-zem-border bg-white px-4 py-3 outline-none focus:border-zem-gold md:col-span-2" aria-label="{{ __('Email address') }}">
                </fieldset>
                <fieldset class="grid gap-4 md:grid-cols-2">
                    <legend class="mb-3 w-full border-b border-zem-border pb-2 text-xs font-extrabold uppercase tracking-wider text-zem-muted">{{ __('Venue details') }}</legend>
                    <input name="restaurant_name" required placeholder="{{ __('Restaurant or hotel name') }}" class="rounded-lg border border-zem-border bg-white px-4 py-3 outline-none focus:border-zem-gold" aria-label="{{ __('Restaurant or hotel name') }}">
                    <input name="location" placeholder="{{ __('Location') }}" class="rounded-lg border border-zem-border bg-white px-4 py-3 outline-none focus:border-zem-gold" aria-label="{{ __('Location') }}">
                </fieldset>
                <fieldset class="grid gap-4">
                    <legend class="mb-3 w-full border-b border-zem-border pb-2 text-xs font-extrabold uppercase tracking-wider text-zem-muted">{{ __('Message') }}</legend>
                    <textarea name="message" placeholder="{{ __('Table or room count, service style, any questions...') }}" rows="4" class="rounded-lg border border-zem-border bg-white px-4 py-3 outline-none focus:border-zem-gold" aria-label="{{ __('Message') }}"></textarea>
                </fieldset>
                <div class="flex flex-col gap-3">
                    <button class="rounded-lg bg-zem-gold px-6 py-3 font-extrabold text-white transition hover:bg-zem-redDark">{{ __('Send request') }}</button>
                    <p class="text-center text-xs text-zem-muted">{{ __("We'll only use your details to respond to this request. No spam, ever.") }}</p>
                </div>
            </form>
        </div>
    </section>

    {{-- Sticky Mobile CTA --}}
    <div class="hidden fixed bottom-0 inset-x-0 z-40 border-t border-zem-border bg-white/95 px-4 py-3 backdrop-blur-lg md:hidden">
        <a href="#demo" class="flex w-full items-center justify-center rounded-lg bg-zem-gold py-3 font-extrabold text-white shadow-xl">{{ __('Request Demo') }}</a>
    </div>

    {{-- Expanded Footer --}}
    <footer class="border-t border-zem-border px-5 py-12 text-sm text-zem-muted md:text-left" itemscope itemtype="https://schema.org/Organization">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-8 md:grid-cols-4">
                <div class="md:col-span-2">
                    <a href="/" class="inline-flex items-center">
                        <img src="{{ asset('logo/zemtab-pantone-1795-c-text-transparent.png') }}" alt="ZemTab" class="h-8 w-auto">
                    </a>
                    <p class="mt-3 max-w-sm leading-6">German-made QR Menu, Table Ordering, and Hotel Room Ordering for modern restaurants, hotels, cafes, and lounges across Ethiopia.</p>
                    <p class="mt-3">Based in <span itemprop="address" itemscope itemtype="https://schema.org/PostalAddress"><span itemprop="addressLocality">Addis Ababa</span>, <span itemprop="addressCountry">Ethiopia</span></span>.</p>
                </div>
                <div>
                    <p class="mb-3 font-bold text-zem-cream">{{ __('Product') }}</p>
                    <ul class="space-y-2">
                        <li><a href="#workflow" class="transition hover:text-zem-gold">{{ __('Workflow') }}</a></li>
                        <li><a href="#features" class="transition hover:text-zem-gold">{{ __('Features') }}</a></li>
                        <li><a href="#pricing" class="transition hover:text-zem-gold">{{ __('Pricing') }}</a></li>
                        <li><a href="#faq" class="transition hover:text-zem-gold">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <p class="mb-3 font-bold text-zem-cream">{{ __('Contact') }}</p>
                    <ul class="space-y-2">
                        <li><a href="mailto:zemtab.support@gmail.com" itemprop="email" class="transition hover:text-zem-gold">zemtab.support@gmail.com</a></li>
                        <li><a href="#demo" class="transition hover:text-zem-gold">{{ __('Request Demo') }}</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-10 grid gap-4 border-t border-zem-border pt-6 text-xs text-zem-muted sm:grid-cols-2 md:grid-cols-4">
                <div>
                    <p class="font-bold text-zem-cream">Ethiopia</p>
                    <p class="mt-1 leading-5">Gabon Street, Woreda 02<br>Addis Ababa</p>
                </div>
                <div>
                    <p class="font-bold text-zem-cream">Germany</p>
                    <p class="mt-1 leading-5">Stuttgart, Baden-Württemberg</p>
                </div>
                <div>
                    <p class="font-bold text-zem-cream">Phone</p>
                    <p class="mt-1 leading-5">ET +251 974 217 074<br>DE +49 160 92988456</p>
                </div>
                <div class="flex md:justify-end">
                    <button id="back-to-top" type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-zem-border bg-white px-3 py-2 font-semibold text-zem-muted transition hover:border-zem-gold hover:text-zem-gold">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                        {{ __('Back to top') }}
                    </button>
                </div>
            </div>
            <div class="mt-8 border-t border-zem-border pt-6 text-xs text-zem-muted">
                <p>&copy; {{ date('Y') }} <span itemprop="name">ZemTab</span>. {{ __('All rights reserved.') }}</p>
            </div>
        </div>
    </footer>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('mobile-menu-toggle');
        const menu = document.getElementById('mobile-menu');
        if (toggle && menu) {
            toggle.addEventListener('click', function() {
                const expanded = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', !expanded);
                menu.classList.toggle('hidden');
            });
        }
        const backToTop = document.getElementById('back-to-top');
        if (backToTop) {
            backToTop.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    });
</script>

@push('structured-data')
@verbatim
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    "name": "ZemTab",
    "applicationCategory": "BusinessApplication",
    "operatingSystem": "Web Browser",
    "softwareVersion": "1.0",
    "offers": {
        "@type": "Offer",
        "price": "2000",
        "priceCurrency": "ETB",
        "availability": "https://schema.org/InStock",
        "seller": { "@type": "Organization", "name": "ZemTab" }
    },
    "aggregateRating": { "@type": "AggregateRating", "ratingValue": "4.8", "ratingCount": "12" },
    "description": "ZemTab is a German-made, Ethiopia-based QR menu, table ordering, hotel room ordering, service request, and staff dashboard system. Guests scan, order, request service, and pay from their phone. No app download needed.",
    "url": "https://zemtab.com",
    "image": "https://zemtab.com/logo/zemtab-pantone-1795-c-icon-text-transparent.png",
    "inLanguage": "en",
    "countriesSupported": "ET"
}
</script>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "ZemTab",
    "url": "https://zemtab.com",
    "logo": "https://zemtab.com/logo/zemtab-pantone-1795-c-icon-text-transparent.png",
    "email": "zemtab.support@gmail.com",
    "address": { "@type": "PostalAddress", "addressLocality": "Addis Ababa", "addressCountry": "ET" },
    "sameAs": []
}
</script>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "ZemTab",
    "description": "German-made, Ethiopia-based QR Menu, Table Ordering, and Hotel Room Ordering system for restaurants, hotels, cafes, and lounges.",
    "url": "https://zemtab.com",
    "telephone": "",
    "email": "zemtab.support@gmail.com",
    "address": { "@type": "PostalAddress", "addressLocality": "Addis Ababa", "addressCountry": "Ethiopia" },
    "geo": { "@type": "GeoCoordinates", "latitude": "9.0192", "longitude": "38.7525" },
    "areaServed": { "@type": "Country", "name": "Ethiopia" },
    "priceRange": "$$"
}
</script>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": "ZemTab",
    "url": "https://zemtab.com",
    "potentialAction": {
        "@type": "SearchAction",
        "target": { "@type": "EntryPoint", "urlTemplate": "https://zemtab.com/r/{restaurant_slug}/table/{table_number}" },
        "query-input": "required name=restaurant_slug,table_number"
    }
}
</script>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        { "@type": "Question", "name": "Do guests need to download an app?", "acceptedAnswer": { "@type": "Answer", "text": "No. Guests simply scan the QR code on their table or in their room with their phone camera and the menu opens instantly in their browser. No app installs, no sign-ups." } },
        { "@type": "Question", "name": "How do I set up QR codes for tables or rooms?", "acceptedAnswer": { "@type": "Answer", "text": "ZemTab generates a unique QR code for each table or hotel room. You can download and print them directly from the dashboard, or we can help with setup." } },
        { "@type": "Question", "name": "What payment methods are supported?", "acceptedAnswer": { "@type": "Answer", "text": "Guests can pay at the end with cash, Telebirr, CBE, bank transfer, or other manual methods. Restaurants and hotels can show payment QR codes and account numbers, then guests upload or show proof to staff." } },
        { "@type": "Question", "name": "How much does it cost?", "acceptedAnswer": { "@type": "Answer", "text": "ZemTab is 2,000 birr per month, plus a one-time 12,000 birr setup charge. The setup charge covers QR printouts and full system setup inside your restaurant or hotel workspace. No commission on orders." } },
        { "@type": "Question", "name": "Can I update menu prices in real-time?", "acceptedAnswer": { "@type": "Answer", "text": "Yes. Any change you make in the dashboard - prices, item names, descriptions, availability - reflects instantly on the QR menu guests see." } },
        { "@type": "Question", "name": "What if I have multiple locations?", "acceptedAnswer": { "@type": "Answer", "text": "Each location runs as its own ZemTab subscription with its own menus, tables or rooms, and staff. This keeps billing and setup simple per venue." } }
    ]
}
</script>
@endverbatim
@endpush
@endsection
