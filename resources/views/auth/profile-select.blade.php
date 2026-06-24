<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Select Profile — {{ $restaurant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { zem: { gold: '#D22630', cream: '#1a1a1a', muted: '#6b7280', border: '#e5e7eb', card: '#ffffff', bg: '#f8fafc' } }, fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'], display: ['Sora', 'Inter', 'ui-sans-serif'] } } } }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-zem-bg min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <img src="{{ asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}" alt="ZemTab" class="h-14 mx-auto mb-4">
            <h1 class="font-display text-2xl font-bold text-zem-cream">{{ $restaurant->name }}</h1>
            <p class="text-sm text-zem-muted mt-1">Select your profile to continue</p>
        </div>

        @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <div class="space-y-3">
            @foreach($profiles as $profile)
                <div class="rounded-xl border border-zem-border bg-zem-card p-5 shadow-sm transition hover:shadow-md" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-full text-sm font-bold text-white @if($profile->role === 'owner_manager') bg-zem-gold @elseif($profile->role === 'cashier') bg-blue-600 @else bg-green-600 @endif">
                                {{ strtoupper(substr($profile->name, 0, 1)) }}
                            </div>
                            <div class="text-left">
                                <p class="font-bold text-zem-cream">{{ $profile->name }}</p>
                                <p class="text-xs text-zem-muted">{{ $profile->roleLabel() }}</p>
                            </div>
                        </div>
                        <svg class="h-5 w-5 text-zem-muted transition" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <form method="post" action="{{ route('restaurant.profile-login') }}" class="mt-4 hidden" :class="open ? '!block' : ''">
                        @csrf
                        <input type="hidden" name="profile_id" value="{{ $profile->id }}">
                        <div class="flex gap-2">
                            <input type="password" name="password" placeholder="Enter profile password" class="flex-1 rounded-lg border border-zem-border bg-white px-3 py-2.5 text-sm">
                            <button class="rounded-lg bg-zem-gold px-5 py-2.5 text-sm font-bold text-white transition hover:opacity-90">Enter</button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="mt-6 text-center">
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="text-sm font-bold text-zem-muted transition hover:text-zem-gold">← Back to login</button>
            </form>
        </div>
    </div>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>