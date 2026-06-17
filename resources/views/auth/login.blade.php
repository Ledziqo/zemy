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
        tailwind.config = { theme: { extend: { colors: { zem: { bg: '#F8FAFC', gold: '#D22630', navy: '#000000', muted: '#667085' } } } } }
    </script>
</head>
<body class="bg-zem-bg text-zem-navy antialiased">
<main class="grid min-h-screen place-items-center bg-[radial-gradient(circle_at_top,rgba(210,38,48,.16),transparent_35%),#F8FAFC] px-5 py-10">
    <form method="post" action="/login" class="w-full max-w-md rounded-2xl border border-[#D8E0E7] bg-white p-6 shadow-2xl">
        @csrf
        <a href="/" class="inline-flex items-center">
            <img src="{{ asset('logo/zemtab-pantone-1795-c-icon-text-transparent.png') }}" alt="ZemTab" class="h-16 w-auto">
        </a>

        <h1 class="mt-8 text-2xl font-extrabold">Sign in</h1>
        <p class="mt-2 text-sm text-zem-muted">Access your restaurant or ZemTab admin dashboard.</p>

        @if($errors->any())
            <div class="mt-5 rounded-lg border border-red-400/40 bg-red-950 px-4 py-3 text-sm">{{ $errors->first() }}</div>
        @endif

        @if(session('success'))
            <div class="mt-5 rounded-lg border border-green-400/40 bg-green-950 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        <div class="mt-6 space-y-4">
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus placeholder="Email" class="w-full rounded-lg border border-[#D8E0E7] bg-white px-4 py-3 outline-none focus:border-zem-gold">
            <input id="password" name="password" type="password" required placeholder="Password" class="w-full rounded-lg border border-[#D8E0E7] bg-white px-4 py-3 outline-none focus:border-zem-gold">
            <label class="flex items-center gap-2 text-sm text-zem-muted"><input type="checkbox" name="remember" value="1"> Remember me</label>
            <button class="w-full rounded-lg bg-zem-gold py-3 font-extrabold text-white transition hover:bg-[#A71D2A]">Login</button>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3">
            <button type="button" data-demo-email="admin@zemtab.test" class="rounded-lg border border-[#D8E0E7] px-3 py-2 text-sm font-bold text-zem-navy hover:border-zem-gold">Login as Admin</button>
            <button type="button" data-demo-email="owner@bolebistro.test" class="rounded-lg border border-[#D8E0E7] px-3 py-2 text-sm font-bold text-zem-navy hover:border-zem-gold">Login as Restaurant</button>
        </div>
        <p class="mt-4 text-xs text-zem-muted">Demo password: password. Staff account: staff@bolebistro.test.</p>
        <a href="/setup" class="mt-4 inline-flex text-xs font-bold text-zem-gold">Setup database</a>
    </form>
</main>
<script>
document.querySelectorAll('[data-demo-email]').forEach((button) => {
    button.addEventListener('click', () => {
        document.getElementById('email').value = button.dataset.demoEmail;
        document.getElementById('password').value = 'password';
        button.closest('form').submit();
    });
});
</script>
</body>
</html>


