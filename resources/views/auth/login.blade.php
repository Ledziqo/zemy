<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Login - ZemTab</title>
    <link rel="icon" type="image/png" href="{{ asset('logo/zemtab-pantone-1795-c-icon-transparent.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { zem: { bg: '#F8FAFC', card: '#FFFFFF', gold: '#D22630', cream: '#000000', muted: '#475467', green: '#16a34a', border: '#D8E0E7', soft: '#EEF3F7' } }, fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'], display: ['Sora', 'Inter', 'ui-sans-serif'] } } } }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_top_right,rgba(210,38,48,.16),transparent_28%),linear-gradient(180deg,#F8FAFC,#EEF3F7)] font-sans">
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-8">
        <a href="{{ route('home') }}" class="mb-8">
            <img src="{{ asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}" alt="ZemTab" class="h-16 w-auto">
        </a>
        <div class="w-full max-w-sm rounded-2xl border border-zem-border bg-zem-card p-6 shadow-xl">
            <h1 class="font-display text-2xl font-extrabold text-center">Welcome back</h1>
            <p class="mt-1 text-center text-sm text-zem-muted">Sign in to your ZemTab dashboard</p>
            <form method="post" action="{{ route('login.store') }}" class="mt-6 grid gap-4">
                @csrf
                @if(session('error'))
                    <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
                @endif
                <div class="grid gap-2">
                    <label class="text-sm font-bold text-zem-muted">Email</label>
                    <input name="email" type="email" required value="{{ old('email') }}" placeholder="you@restaurant.com" class="rounded-lg border border-zem-border bg-zem-bg px-4 py-3 outline-none focus:border-zem-gold">
                </div>
                <div class="grid gap-2">
                    <label class="text-sm font-bold text-zem-muted">Password</label>
                    <input name="password" type="password" required placeholder="Your password" class="rounded-lg border border-zem-border bg-zem-bg px-4 py-3 outline-none focus:border-zem-gold">
                </div>
                <button class="rounded-lg bg-zem-gold py-3 font-bold text-white transition hover:opacity-90">Sign in</button>
            </form>
            <div class="mt-6 border-t border-zem-border pt-4 text-center">
                <details class="mb-4 rounded-xl border border-zem-border bg-zem-bg text-left">
                    <summary class="cursor-pointer px-4 py-3 text-center text-sm font-bold text-zem-muted hover:text-zem-gold">Need help?</summary>
                    <div class="border-t border-zem-border px-4 py-4 text-sm text-zem-muted">
                        <p class="font-bold text-black">Contact ZemTab support</p>
                        <p class="mt-3"><a href="tel:+251974217074" class="font-semibold hover:text-zem-gold">Ethiopia: +251 974 217 074</a></p>
                        <p class="mt-2"><a href="https://t.me/Zemtab" target="_blank" rel="noopener noreferrer" class="font-semibold hover:text-zem-gold">Telegram: @Zemtab</a></p>
                    </div>
                </details>
                <a href="{{ route('home') }}" class="text-sm text-zem-muted hover:text-zem-gold">Back to ZemTab.com</a>
            </div>
        </div>
    </div>
</body>
</html>
