@extends('layouts.app', ['title' => 'ZemTab - Scan. Order. Pay.'])

@section('content')
<main class="scroll-smooth overflow-hidden bg-zem-bg text-white">
    <header class="fixed inset-x-0 top-0 z-40 border-b border-white/10 bg-black/75 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4">
            <a href="/" class="inline-flex items-center">
                <img src="{{ asset('logo/zemtab-full-transparent-dark.png') }}" alt="ZemTab" class="h-12 w-auto">
            </a>
            <nav class="hidden items-center gap-6 text-sm font-semibold text-zem-muted md:flex">
                <a class="transition hover:text-white" href="#workflow">Workflow</a>
                <a class="transition hover:text-white" href="#features">Features</a>
                <a class="transition hover:text-white" href="#pricing">Pricing</a>
                <a class="transition hover:text-white" href="#demo">Demo</a>
            </nav>
            <a href="#demo" class="rounded-lg bg-white px-4 py-2 text-sm font-extrabold text-black transition hover:bg-zem-gold hover:text-white">Request Demo</a>
        </div>
    </header>

    <section class="relative px-5 pt-28">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_18%,rgba(239,35,60,.32),transparent_32%),radial-gradient(circle_at_78%_8%,rgba(255,255,255,.12),transparent_22%),linear-gradient(135deg,#050505_0%,#111_45%,#050505_100%)]"></div>
        <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-zem-bg to-transparent"></div>
        <div class="relative mx-auto grid min-h-[88vh] max-w-7xl items-center gap-10 pb-20 lg:grid-cols-[1fr_.9fr]">
            <div class="max-w-3xl">
                <p class="mb-5 inline-flex rounded-full border border-zem-gold/40 bg-zem-gold/10 px-4 py-2 text-xs font-extrabold uppercase tracking-[.26em] text-red-100">Scan. Order. Pay.</p>
                <img src="{{ asset('logo/zemtab-full-transparent-dark.png') }}" alt="ZemTab" class="h-auto w-full max-w-xl">
                <p class="mt-5 max-w-2xl text-xl leading-8 text-zem-muted">A modern QR menu, table ordering, waiter request, and restaurant dashboard system built for faster service and cleaner operations.</p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="#demo" class="rounded-lg bg-zem-gold px-6 py-3 font-extrabold text-white shadow-xl shadow-zem-gold/25 transition hover:bg-red-700">Request Demo</a>
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
                            <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-zem-gold font-display text-xl font-extrabold shadow-xl shadow-zem-gold/30">IMG</div>
                            <p class="mt-5 font-display text-2xl font-extrabold">Image placeholder</p>
                            <p class="mt-3 text-sm leading-6 text-zem-muted">Replace this area with a restaurant, QR menu, or product screenshot image.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="workflow" class="scroll-mt-24 border-y border-white/10 bg-white/[.03] py-16">
        <div class="mx-auto max-w-7xl px-5">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 class="font-display text-3xl font-extrabold md:text-4xl">From scan to service in one flow</h2>
                <p class="max-w-xl text-zem-muted">The whole dining loop runs from the guest phone to the staff dashboard without app installs or awkward handoffs.</p>
            </div>
            <div class="mt-8 grid gap-4 md:grid-cols-5">
                @foreach([['Scan','Guests scan the QR code on their table.'],['Browse','They view categories, menu items, details, and prices.'],['Order','They add items, quantities, and special notes.'],['Serve','Staff receive live orders in the restaurant dashboard.'],['Pay','Guests can choose cash, cashier, transfer, or future integrated payment options.']] as $step)
                    <div class="rounded-xl border border-white/10 bg-black p-5 transition hover:-translate-y-1 hover:border-zem-gold/50">
                        <p class="text-sm font-extrabold text-zem-gold">0{{ $loop->iteration }}</p>
                        <h3 class="mt-4 font-display text-xl font-bold">{{ $step[0] }}</h3>
                        <p class="mt-3 text-sm leading-6 text-zem-muted">{{ $step[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto grid max-w-7xl gap-5 px-5 py-16 lg:grid-cols-3">
        @foreach([['For guests','Fast mobile menus, simple ordering, waiter calls, bill requests, and no app download.'],['For staff','Live table orders, service request queues, status updates, and fewer missed requests.'],['For owners','Instant menu updates, availability control, multi-table QR links, subscriptions, and admin oversight.']] as $audience)
            <div class="rounded-xl border border-white/10 bg-white/[.04] p-6">
                <h2 class="font-display text-2xl font-extrabold">{{ $audience[0] }}</h2>
                <p class="mt-4 leading-7 text-zem-muted">{{ $audience[1] }}</p>
            </div>
        @endforeach
    </section>

    <section id="features" class="scroll-mt-24 mx-auto max-w-7xl px-5 py-16">
        <div class="max-w-3xl">
            <h2 class="font-display text-3xl font-extrabold md:text-4xl">Simple tools for faster table service</h2>
            <p class="mt-4 leading-7 text-zem-muted">Guests order from their phone. Staff see requests instantly. Owners update the menu anytime.</p>
        </div>
        <div class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            @foreach([['Scan menu','Customers scan a table QR and open the menu instantly.'],['Place order','They choose items, add notes, and send the order.'],['Call staff','Guests can call a waiter or request the bill from the table.'],['Manage live','Staff track orders, update status, and edit menu availability.']] as $feature)
                <div class="rounded-xl border border-white/10 bg-black p-6 transition hover:border-zem-gold/50 hover:bg-zem-gold/10">
                    <h3 class="font-display text-xl font-extrabold">{{ $feature[0] }}</h3>
                    <p class="mt-3 leading-6 text-zem-muted">{{ $feature[1] }}</p>
                </div>
            @endforeach
        </div>
    </section>

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
                @foreach([['“Customers do not need to wait for a menu anymore.”','Cafe owner'],['“The waiter calls and bill requests are easy for staff to follow.”','Restaurant manager'],['“Changing menu availability during service is quick.”','Operations lead']] as $vouch)
                    <div class="rounded-xl border border-white/10 bg-black p-5">
                        <p class="leading-7 text-white">{{ $vouch[0] }}</p>
                        <p class="mt-4 text-sm font-bold text-zem-gold">{{ $vouch[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

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

    <section id="pricing" class="scroll-mt-24 mx-auto max-w-7xl px-5 py-16">
        <h2 class="font-display text-3xl font-extrabold md:text-4xl">One simple subscription</h2>
        <p class="mt-4 max-w-2xl text-zem-muted">Everything needed to run QR menus, table orders, service requests, and a live restaurant dashboard.</p>
        <div class="mt-8 max-w-2xl rounded-2xl border border-zem-gold bg-black p-7 shadow-2xl shadow-zem-gold/10">
            <p class="text-sm font-extrabold uppercase tracking-[.22em] text-zem-gold">Monthly plan</p>
            <h3 class="mt-3 font-display text-5xl font-extrabold text-white">3,500 birr</h3>
            <p class="mt-2 text-zem-muted">per month</p>
            <div class="mt-6 grid gap-3 text-sm font-semibold text-white sm:grid-cols-2">
                @foreach(['Digital QR menu','Table ordering','Call waiter and bill requests','Live staff dashboard','Menu availability control','Basic setup support'] as $item)
                    <p class="rounded-lg border border-white/10 bg-white/[.04] px-4 py-3">{{ $item }}</p>
                @endforeach
            </div>
            <a href="#demo" class="mt-7 inline-flex rounded-lg bg-zem-gold px-6 py-3 font-extrabold text-white transition hover:bg-red-700">Request Demo</a>
        </div>
    </section>

    <section id="demo" class="scroll-mt-24 border-t border-white/10 bg-white/[.03] py-16">
        <div class="mx-auto grid max-w-7xl gap-8 px-5 lg:grid-cols-[.8fr_1.2fr]">
            <div>
                <h2 class="font-display text-3xl font-extrabold md:text-4xl">Request a demo</h2>
                <p class="mt-3 leading-7 text-zem-muted">Tell us about your restaurant, table count, and service style. ZemTab can be set up for a single cafe, a full restaurant, or a multi-location operation.</p>
            </div>
            <form method="post" action="{{ route('demo-requests.store') }}" class="grid gap-4 rounded-xl border border-white/10 bg-black p-5 md:grid-cols-2">
                @csrf
                <input name="name" required placeholder="Name" class="rounded-lg border border-white/10 bg-white/[.04] px-4 py-3 outline-none focus:border-zem-gold">
                <input name="restaurant_name" required placeholder="Restaurant name" class="rounded-lg border border-white/10 bg-white/[.04] px-4 py-3 outline-none focus:border-zem-gold">
                <input name="phone" required placeholder="Phone number" class="rounded-lg border border-white/10 bg-white/[.04] px-4 py-3 outline-none focus:border-zem-gold">
                <input name="email" type="email" placeholder="Email optional" class="rounded-lg border border-white/10 bg-white/[.04] px-4 py-3 outline-none focus:border-zem-gold">
                <input name="location" placeholder="Location" class="rounded-lg border border-white/10 bg-white/[.04] px-4 py-3 outline-none focus:border-zem-gold md:col-span-2">
                <textarea name="message" placeholder="Message" rows="4" class="rounded-lg border border-white/10 bg-white/[.04] px-4 py-3 outline-none focus:border-zem-gold md:col-span-2"></textarea>
                <button class="rounded-lg bg-zem-gold px-6 py-3 font-extrabold text-white transition hover:bg-red-700 md:col-span-2">Send request</button>
            </form>
        </div>
    </section>

    <footer class="border-t border-white/10 px-5 py-8 text-center text-sm text-zem-muted">ZemTab - QR Menu & Table Ordering for modern restaurants.</footer>
</main>
@endsection
