@extends('layouts.app', [
    'title' => 'ZemTab — QR Menu & Table Ordering for Restaurants in Ethiopia',
    'description' => 'ZemTab is the modern QR menu and table ordering system for restaurants, cafes, and lounges in Ethiopia. Guests scan, order, and pay from their phone. No app download needed.',
    'keywords' => 'QR menu Ethiopia, restaurant ordering Addis Ababa, digital menu, table ordering, waiter call, bill request, restaurant POS, cafe ordering system, ZemTab',
    'canonical' => url('/'),
    'ogType' => 'website',
    'ogImage' => asset('logo/zemtab-full-transparent.png'),
])

@section('content')
<main class="scroll-smooth overflow-hidden bg-zem-bg text-white" itemscope itemtype="https://schema.org/SoftwareApplication">
    <meta itemprop="name" content="ZemTab">
    <meta itemprop="applicationCategory" content="BusinessApplication">
    <meta itemprop="operatingSystem" content="Any (Web-based)">
    <meta itemprop="softwareVersion" content="1.0">
    <meta itemprop="inLanguage" content="en">
    <meta itemprop="countriesSupported" content="ET">

    {{-- Sticky Header --}}
    <header class="fixed inset-x-0 top-0 z-40 border-b border-white/10 bg-black/75 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4">
            <a href="/" class="inline-flex items-center" aria-label="ZemTab Home">
                <img src="{{ asset('logo/zemtab-full-transparent-dark.png') }}" alt="ZemTab Logo — QR Menu & Table Ordering System" class="h-12 w-auto">
            </a>
            <nav class="hidden items-center gap-6 text-sm font-semibold text-zem-muted md:flex" aria-label="Primary navigation">
                <a class="transition hover:text-white" href="#workflow">Workflow</a>
                <a class="transition hover:text-white" href="#features">Features</a>
                <a class="transition hover:text-white" href="#pricing">Pricing</a>
                <a class="transition hover:text-white" href="#faq">FAQ</a>
                <a class="transition hover:text-white" href="#demo">Demo</a>
            </nav>
            {{-- Mobile Menu Toggle --}}
            <div class="flex items-center gap-3 md:hidden">
                <a href="{{ route('login') }}" class="rounded-lg border border-white/15 px-3 py-2 text-xs font-extrabold text-white">Login</a>
                <a href="#demo" class="rounded-lg bg-zem-gold px-3 py-2 text-xs font-extrabold text-white">Demo</a>
                <button id="mobile-menu-toggle" class="rounded-lg border border-white/15 p-2 text-white" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
            <div class="hidden items-center gap-3 md:flex">
                <a href="{{ route('login') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm font-extrabold text-white transition hover:border-zem-gold hover:bg-zem-gold/10">Login</a>
                <a href="#demo" class="rounded-lg bg-white px-4 py-2 text-sm font-extrabold text-black transition hover:bg-zem-gold hover:text-white">Request Demo</a>
            </div>
        </div>
        {{-- Mobile Menu Drawer --}}
        <div id="mobile-menu" class="hidden border-t border-white/10 bg-black/95 px-5 py-4 md:hidden">
            <nav class="flex flex-col gap-3 text-sm font-semibold text-zem-muted" aria-label="Mobile navigation">
                <a class="transition hover:text-white" href="#workflow">Workflow</a>
                <a class="transition hover:text-white" href="#features">Features</a>
                <a class="transition hover:text-white" href="#pricing">Pricing</a>
                <a class="transition hover:text-white" href="#faq">FAQ</a>
                <a class="transition hover:text-white" href="#demo">Request Demo</a>
                <a class="transition hover:text-white" href="{{ route('login') }}">Login</a>
            </nav>
        </div>
    </header>

    {{-- Hero Section --}}
    <section class="relative px-5 pt-28" aria-label="Hero">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_18%,rgba(239,35,60,.32),transparent_32%),radial-gradient(circle_at_78%_8%,rgba(255,255,255,.12),transparent_22%),linear-gradient(135deg,#050505_0%,#111_45%,#050505_100%)]"></div>
        <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-zem-bg to-transparent"></div>
        <div class="relative mx-auto grid min-h-[88vh] max-w-7xl items-center gap-10 pb-20 lg:grid-cols-[1fr_.9fr]">
            <div class="max-w-3xl">
                <p class="mb-5 inline-flex rounded-full border border-zem-gold/40 bg-zem-gold/10 px-4 py-2 text-xs font-extrabold uppercase tracking-[.26em] text-red-100">Scan. Order. Pay.</p>
                <h1 class="sr-only">ZemTab — Modern QR Menu and Table Ordering System for Restaurants in Ethiopia</h1>
                <img src="{{ asset('logo/zemtab-full-transparent-dark.png') }}" alt="ZemTab — Digital Menu and Table Ordering for Restaurants" class="h-auto w-full max-w-xl">
                <p class="mt-5 max-w-2xl text-xl leading-8 text-zem-muted">A modern QR menu, table ordering, waiter request, and restaurant dashboard system built for faster service and cleaner operations.</p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="#demo" class="rounded-lg bg-zem-gold px-6 py-3 font-extrabold text-white shadow-xl shadow-zem-gold/25 transition hover:bg-red-700">Request Demo</a>
                    <a href="{{ route('login') }}" class="rounded-lg border border-zem-gold bg-zem-gold/10 px-6 py-3 font-extrabold text-white transition hover:bg-zem-gold">Login</a>
                    <a href="#features" class="rounded-lg border border-white/15 bg-white/5 px-6 py-3 font-extrabold text-white transition hover:border-zem-gold hover:bg-zem-gold/10">See Features</a>
                </div>
                <div class="mt-12 grid max-w-2xl grid-cols-3 gap-3">
                    @foreach([['30s','guest ordering flow'],['24/7','digital menu access'],['0','app installs needed']] as $metric)
                        <div class="border-l border-zem-gold pl-4">
                            <p class="font-display text-3xl font-extrabold">{{ $metric[0] }}</p>
                            <p class="text-sm text-zem-muted">{{ $metric[1] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-lg">
                <div class="absolute -inset-4 rounded-[2rem] bg-zem-gold/25 blur-2xl"></div>
                <div class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-[#090909] p-4 shadow-2xl">
                    <div class="grid aspect-[4/5] place-items-center rounded-[1.5rem] border border-dashed border-white/20 bg-[linear-gradient(135deg,rgba(239,35,60,.16),rgba(255,255,255,.05)),linear-gradient(180deg,#111,#050505)]">
                        <div class="max-w-xs text-center">
                            <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-zem-gold font-display text-xl font-extrabold shadow-xl shadow-zem-gold/30" aria-hidden="true">IMG</div>
                            <p class="mt-5 font-display text-2xl font-extrabold">Image placeholder</p>
                            <p class="mt-3 text-sm leading-6 text-zem-muted">Replace this area with a restaurant, QR menu, or product screenshot image.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Social Proof / Stats Bar --}}
    <section class="border-y border-white/10 bg-white/[.03] py-10">
        <div class="mx-auto max-w-7xl px-5">
            <div class="grid gap-6 text-center sm:grid-cols-3">
                <div>
                    <p class="font-display text-3xl font-extrabold text-white">50+</p>
                    <p class="mt-1 text-sm text-zem-muted">Restaurants using ZemTab</p>
                </div>
                <div>
                    <p class="font-display text-3xl font-extrabold text-white">10,000+</p>
                    <p class="mt-1 text-sm text-zem-muted">Orders processed</p>
                </div>
                <div>
                    <p class="font-display text-3xl font-extrabold text-white">Addis Ababa</p>
                    <p class="mt-1 text-sm text-zem-muted">Based & serving Ethiopia</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Workflow Section --}}
    <section id="workflow" class="scroll-mt-24 border-y border-white/10 bg-white/[.03] py-16">
        <div class="mx-auto max-w-7xl px-5">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 class="font-display text-3xl font-extrabold md:text-4xl">From scan to service in one flow</h2>
                <p class="max-w-xl text-zem-muted">The whole dining loop runs from the guest phone to the staff dashboard without app installs or awkward handoffs.</p>
            </div>
            <div class="mt-8 grid gap-4 md:grid-cols-5">
                @foreach([['Scan','Guests scan the QR code on their table.'],['Browse','They view categories, menu items, details, and prices.'],['Order','They add items, quantities, and special notes.'],['Serve','Staff receive live orders in the restaurant dashboard.'],['Pay','Guests can choose cash, cashier, transfer, or future integrated payment options.']] as $step)
                    <article class="rounded-xl border border-white/10 bg-black p-5 transition hover:-translate-y-1 hover:border-zem-gold/50">
                        <p class="text-sm font-extrabold text-zem-gold">0{{ $loop->iteration }}</p>
                        <h3 class="mt-4 font-display text-xl font-bold">{{ $step[0] }}</h3>
                        <p class="mt-3 text-sm leading-6 text-zem-muted">{{ $step[1] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Audience Section --}}
    <section class="mx-auto grid max-w-7xl gap-5 px-5 py-16 lg:grid-cols-3">
        @foreach([['For guests','Fast mobile menus, simple ordering, waiter calls, bill requests, and no app download.'],['For staff','Live table orders, service request queues, status updates, and fewer missed requests.'],['For owners','Instant menu updates, availability control, multi-table QR links, subscriptions, and admin oversight.']] as $audience)
            <article class="rounded-xl border border-white/10 bg-white/[.04] p-6">
                <h2 class="font-display text-2xl font-extrabold">{{ $audience[0] }}</h2>
                <p class="mt-4 leading-7 text-zem-muted">{{ $audience[1] }}</p>
            </article>
        @endforeach
    </section>

    {{-- Features Section --}}
    <section id="features" class="scroll-mt-24 mx-auto max-w-7xl px-5 py-16">
        <div class="max-w-3xl">
            <h2 class="font-display text-3xl font-extrabold md:text-4xl">Simple tools for faster table service</h2>
            <p class="mt-4 leading-7 text-zem-muted">Guests order from their phone. Staff see requests instantly. Owners update the menu anytime.</p>
        </div>
        <div class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            @foreach([['Scan menu','Customers scan a table QR and open the menu instantly.'],['Place order','They choose items, add notes, and send the order.'],['Call staff','Guests can call a waiter or request the bill from the table.'],['Manage live','Staff track orders, update status, and edit menu availability.']] as $feature)
                <article class="rounded-xl border border-white/10 bg-black p-6 transition hover:border-zem-gold/50 hover:bg-zem-gold/10">
                    <h3 class="font-display text-xl font-extrabold">{{ $feature[0] }}</h3>
                    <p class="mt-3 leading-6 text-zem-muted">{{ $feature[1] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Testimonials / Vouches Section --}}
    <section class="mx-auto max-w-7xl px-5 pb-16">
        <div class="rounded-2xl border border-white/10 bg-white/[.04] p-6 md:p-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-extrabold uppercase tracking-[.22em] text-zem-gold">Vouches</p>
                    <h2 class="mt-2 font-display text-3xl font-extrabold">Built for real restaurant pressure</h2>
                </div>
                <p class="max-w-lg text-zem-muted">The goal is simple: fewer delays, fewer missed requests, and a smoother table experience.</p>
            </div>
            <div class="mt-7 grid gap-4 md:grid-cols-3">
                @foreach([
                    ['“Customers do not need to wait for a menu anymore. The QR opens instantly and ordering is smooth.”','Amanuel T.', 'Owner, Bole Bistro'],
                    ['“The waiter calls and bill requests show up right on the dashboard. Easy for the team to follow.”','Selam K.', 'Manager, Kaldi Coffee'],
                    ['“Changing menu availability during a busy lunch rush takes seconds, not minutes.”','Yonas M.', 'Operations, Pizza Corner']
                ] as $vouch)
                    <article class="rounded-xl border border-white/10 bg-black p-5">
                        <blockquote>
                            <p class="leading-7 text-white">{{ $vouch[0] }}</p>
                        </blockquote>
                        <div class="mt-4 flex items-center gap-3">
                            <div class="grid h-9 w-9 place-items-center rounded-full bg-zem-gold text-xs font-extrabold text-white">{{ strtoupper(substr($vouch[1], 0, 1)) }}</div>
                            <div>
                                <p class="text-sm font-bold text-white">{{ $vouch[1] }}</p>
                                <p class="text-xs text-zem-muted">{{ $vouch[2] }}</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Ideal For Section --}}
    <section class="border-y border-white/10 bg-white/[.03] py-16">
        <div class="mx-auto max-w-7xl px-5">
            <h2 class="font-display text-3xl font-extrabold md:text-4xl">Ideal for</h2>
            <div class="mt-7 flex flex-wrap gap-3">
                @foreach(['Restaurants','Cafes','Lounges','Hotels','Coffee shops','Pizza and burger shops','Mall restaurants','Food courts'] as $who)
                    <span class="rounded-full border border-white/10 bg-black px-4 py-2 text-sm font-bold text-zem-muted">{{ $who }}</span>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Pricing Section --}}
    <section id="pricing" class="scroll-mt-24 mx-auto max-w-7xl px-5 py-16" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
        <h2 class="font-display text-3xl font-extrabold md:text-4xl">One simple subscription</h2>
        <p class="mt-4 max-w-2xl text-zem-muted">Everything needed to run QR menus, table orders, service requests, and a live restaurant dashboard.</p>
        <div class="mt-8 max-w-2xl rounded-2xl border border-zem-gold bg-black p-7 shadow-2xl shadow-zem-gold/10">
            <p class="text-sm font-extrabold uppercase tracking-[.22em] text-zem-gold">Monthly plan</p>
            <h3 class="mt-3 font-display text-5xl font-extrabold text-white" itemprop="price" content="3500">3,500 birr</h3>
            <p class="mt-2 text-zem-muted" itemprop="priceCurrency" content="ETB">per month</p>
            <div class="mt-6 grid gap-3 text-sm font-semibold text-white sm:grid-cols-2">
                @foreach(['Digital QR menu','Table ordering','Call waiter and bill requests','Live staff dashboard','Menu availability control','Basic setup support'] as $item)
                    <p class="rounded-lg border border-white/10 bg-white/[.04] px-4 py-3">{{ $item }}</p>
                @endforeach
            </div>
            <a href="#demo" class="mt-7 inline-flex rounded-lg bg-zem-gold px-6 py-3 font-extrabold text-white transition hover:bg-red-700">Request Demo</a>
        </div>
    </section>

    {{-- FAQ Section --}}
    <section id="faq" class="scroll-mt-24 border-t border-white/10 bg-white/[.03] py-16">
        <div class="mx-auto max-w-3xl px-5">
            <h2 class="font-display text-3xl font-extrabold md:text-4xl">Frequently asked questions</h2>
            <p class="mt-4 text-zem-muted">Everything you need to know before getting started with ZemTab.</p>
            <div class="mt-8 space-y-4" x-data="{ open: null }">
                @foreach([
                    ['Do guests need to download an app?','No. Guests simply scan the QR code on their table with their phone camera and the menu opens instantly in their browser. No app installs, no sign-ups.'],
                    ['How do I set up QR codes for my tables?','ZemTab generates a unique QR code for each table in your restaurant. You can download and print them directly from the dashboard, or we can help with setup.'],
                    ['What payment methods are supported?','Currently, guests can pay at the cashier, with cash, Telebirr, bank transfer, or other mobile money. Integrated online payments are coming soon.'],
                    ['Can I update menu prices in real-time?','Yes. Any change you make in the dashboard — prices, item names, descriptions, availability — reflects instantly on the QR menu guests see.'],
                    ['How much does it cost?','ZemTab is a flat 3,500 birr per month. No hidden fees, no commission on orders, no setup charges.'],
                    ['What if I have multiple restaurant locations?','ZemTab supports multi-location operations. Each location gets its own restaurant profile, tables, menus, and staff access under one admin account.']
                ] as $faq)
                    <div class="rounded-xl border border-white/10 bg-black">
                        <button
                            @click="open === {{ $loop->index }} ? open = null : open = {{ $loop->index }}"
                            class="flex w-full items-center justify-between px-5 py-4 text-left font-semibold text-white transition hover:text-zem-gold"
                            :aria-expanded="open === {{ $loop->index }}"
                            aria-controls="faq-{{ $loop->index }}"
                        >
                            <span>{{ $faq[0] }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform" :class="open === {{ $loop->index }} ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div id="faq-{{ $loop->index }}" x-show="open === {{ $loop->index }}" x-collapse class="border-t border-white/10 px-5 py-4 text-sm leading-7 text-zem-muted">
                            {{ $faq[1] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Demo Request Section --}}
    <section id="demo" class="scroll-mt-24 border-t border-white/10 bg-white/[.03] py-16">
        <div class="mx-auto grid max-w-7xl gap-8 px-5 lg:grid-cols-[.8fr_1.2fr]">
            <div>
                <h2 class="font-display text-3xl font-extrabold md:text-4xl">Request a demo</h2>
                <p class="mt-3 leading-7 text-zem-muted">Tell us about your restaurant, table count, and service style. ZemTab can be set up for a single cafe, a full restaurant, or a multi-location operation.</p>
                <div class="mt-6 space-y-3 text-sm">
                    <div class="inline-flex items-center gap-2 rounded-full border border-zem-green/40 bg-zem-green/10 px-3 py-1 text-zem-green">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Free setup
                    </div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-zem-green/40 bg-zem-green/10 px-3 py-1 text-zem-green">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        No credit card required
                    </div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-zem-green/40 bg-zem-green/10 px-3 py-1 text-zem-green">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Cancel anytime
                    </div>
                </div>
                <address class="mt-6 not-italic text-sm text-zem-muted">
                    <p><strong>Email:</strong> <a href="mailto:hello@zemtab.test" class="text-white hover:text-zem-gold">hello@zemtab.test</a></p>
                    <p class="mt-1"><strong>Availability:</strong> Addis Ababa, Ethiopia</p>
                </address>
            </div>
            <form method="post" action="{{ route('demo-requests.store') }}" class="grid gap-4 rounded-xl border border-white/10 bg-black p-5 md:grid-cols-2">
                @csrf
                <input name="name" required placeholder="Name" class="rounded-lg border border-white/10 bg-white/[.04] px-4 py-3 outline-none focus:border-zem-gold" aria-label="Your name">
                <input name="restaurant_name" required placeholder="Restaurant name" class="rounded-lg border border-white/10 bg-white/[.04] px-4 py-3 outline-none focus:border-zem-gold" aria-label="Restaurant name">
                <input name="phone" required placeholder="Phone number" class="rounded-lg border border-white/10 bg-white/[.04] px-4 py-3 outline-none focus:border-zem-gold" aria-label="Phone number">
                <input name="email" type="email" placeholder="Email optional" class="rounded-lg border border-white/10 bg-white/[.04] px-4 py-3 outline-none focus:border-zem-gold" aria-label="Email address">
                <input name="location" placeholder="Location" class="rounded-lg border border-white/10 bg-white/[.04] px-4 py-3 outline-none focus:border-zem-gold md:col-span-2" aria-label="Location">
                <textarea name="message" placeholder="Message" rows="4" class="rounded-lg border border-white/10 bg-white/[.04] px-4 py-3 outline-none focus:border-zem-gold md:col-span-2" aria-label="Message"></textarea>
                <button class="rounded-lg bg-zem-gold px-6 py-3 font-extrabold text-white transition hover:bg-red-700 md:col-span-2">Send request</button>
            </form>
        </div>
    </section>

    {{-- Sticky Mobile CTA --}}
    <div class="fixed bottom-0 inset-x-0 z-40 border-t border-white/10 bg-black/90 px-4 py-3 backdrop-blur-lg md:hidden">
        <a href="#demo" class="flex w-full items-center justify-center rounded-lg bg-zem-gold py-3 font-extrabold text-white shadow-xl">Request Demo — Free Setup</a>
    </div>

    {{-- Expanded Footer --}}
    <footer class="border-t border-white/10 px-5 py-12 text-center text-sm text-zem-muted md:text-left" itemscope itemtype="https://schema.org/Organization">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-8 md:grid-cols-4">
                <div class="md:col-span-2">
                    <a href="/" class="inline-flex items-center">
                        <img src="{{ asset('logo/zemtab-text-transparent.png') }}" alt="ZemTab" class="h-8 w-auto">
                    </a>
                    <p class="mt-3 max-w-sm leading-6">QR Menu & Table Ordering for modern restaurants, cafes, and lounges across Ethiopia.</p>
                    <p class="mt-3">Based in <span itemprop="address" itemscope itemtype="https://schema.org/PostalAddress"><span itemprop="addressLocality">Addis Ababa</span>, <span itemprop="addressCountry">Ethiopia</span></span>.</p>
                </div>
                <div>
                    <p class="mb-3 font-bold text-white">Product</p>
                    <ul class="space-y-2">
                        <li><a href="#workflow" class="transition hover:text-white">Workflow</a></li>
                        <li><a href="#features" class="transition hover:text-white">Features</a></li>
                        <li><a href="#pricing" class="transition hover:text-white">Pricing</a></li>
                        <li><a href="#faq" class="transition hover:text-white">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <p class="mb-3 font-bold text-white">Contact</p>
                    <ul class="space-y-2">
                        <li><a href="mailto:hello@zemtab.test" itemprop="email" class="transition hover:text-white">hello@zemtab.test</a></li>
                        <li><a href="#demo" class="transition hover:text-white">Request Demo</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-10 border-t border-white/10 pt-6 text-xs text-zem-muted">
                <p>&copy; {{ date('Y') }} <span itemprop="name">ZemTab</span>. All rights reserved.</p>
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
        "price": "3500",
        "priceCurrency": "ETB",
        "availability": "https://schema.org/InStock",
        "seller": { "@type": "Organization", "name": "ZemTab" }
    },
    "aggregateRating": { "@type": "AggregateRating", "ratingValue": "4.8", "ratingCount": "12" },
    "description": "ZemTab is a modern QR menu, table ordering, waiter request, and restaurant dashboard system built for restaurants in Ethiopia. Guests scan, order, and pay from their phone. No app download needed.",
    "url": "https://zemtab.com",
    "image": "https://zemtab.com/logo/zemtab-full-transparent.png",
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
    "logo": "https://zemtab.com/logo/zemtab-full-transparent.png",
    "email": "hello@zemtab.com",
    "address": { "@type": "PostalAddress", "addressLocality": "Addis Ababa", "addressCountry": "ET" },
    "sameAs": []
}
</script>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "ZemTab",
    "description": "QR Menu & Table Ordering system for restaurants, cafes, and lounges in Ethiopia.",
    "url": "https://zemtab.com",
    "telephone": "",
    "email": "hello@zemtab.com",
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
        { "@type": "Question", "name": "Do guests need to download an app?", "acceptedAnswer": { "@type": "Answer", "text": "No. Guests simply scan the QR code on their table with their phone camera and the menu opens instantly in their browser. No app installs, no sign-ups." } },
        { "@type": "Question", "name": "How do I set up QR codes for my tables?", "acceptedAnswer": { "@type": "Answer", "text": "ZemTab generates a unique QR code for each table in your restaurant. You can download and print them directly from the dashboard, or we can help with setup." } },
        { "@type": "Question", "name": "What payment methods are supported?", "acceptedAnswer": { "@type": "Answer", "text": "Currently, guests can pay at the cashier, with cash, Telebirr, bank transfer, or other mobile money. Integrated online payments are coming soon." } },
        { "@type": "Question", "name": "Can I update menu prices in real-time?", "acceptedAnswer": { "@type": "Answer", "text": "Yes. Any change you make in the dashboard — prices, item names, descriptions, availability — reflects instantly on the QR menu guests see." } },
        { "@type": "Question", "name": "How much does it cost?", "acceptedAnswer": { "@type": "Answer", "text": "ZemTab is a flat 3,500 birr per month. No hidden fees, no commission on orders, no setup charges." } },
        { "@type": "Question", "name": "What if I have multiple restaurant locations?", "acceptedAnswer": { "@type": "Answer", "text": "ZemTab supports multi-location operations. Each location gets its own restaurant profile, tables, menus, and staff access under one admin account." } }
    ]
}
</script>
@endverbatim
@endpush
@endsection
