@extends('layouts.app', [
    'title' => 'Restaurant Login — ZemTab',
    'description' => 'Sign in to your ZemTab restaurant dashboard. Manage your digital QR menu, table orders, and staff operations.',
    'robots' => 'noindex, nofollow',
])

@section('content')
<main class="grid min-h-screen place-items-center bg-[radial-gradient(circle_at_top,rgba(239,35,60,.25),transparent_35%),#050505] px-5 py-10">
    <form method="post" action="{{ route('login.store') }}" class="w-full max-w-md rounded-2xl border border-white/10 bg-white/[.04] p-6 shadow-2xl backdrop-blur">
        @csrf
        <a href="{{ route('home') }}" class="inline-flex items-center"><img src="{{ asset('logo/zemtab-full-transparent-dark.png') }}" alt="ZemTab" class="h-16 w-auto"></a>
        <h1 class="mt-8 font-display text-2xl font-bold">Sign in</h1>
        <p class="mt-2 text-sm text-zem-muted">Access your restaurant or SaaS admin dashboard.</p>
        <div class="mt-6 space-y-4">
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus placeholder="Email" class="w-full rounded-lg border border-white/10 bg-black px-4 py-3 outline-none focus:border-zem-gold">
            <input id="password" name="password" type="password" required placeholder="Password" class="w-full rounded-lg border border-white/10 bg-black px-4 py-3 outline-none focus:border-zem-gold">
            <label class="flex items-center gap-2 text-sm text-zem-muted"><input type="checkbox" name="remember" value="1"> Remember me</label>
            <button class="w-full rounded-lg bg-zem-gold py-3 font-extrabold text-white transition hover:bg-red-700">Login</button>
        </div>
        <div class="mt-5 grid grid-cols-2 gap-3">
            <button type="button" data-demo-email="admin@zemtab.test" class="rounded-lg border border-white/10 px-3 py-2 text-sm font-bold text-white hover:border-zem-gold">Login as Admin</button>
            <button type="button" data-demo-email="owner@bolebistro.test" class="rounded-lg border border-white/10 px-3 py-2 text-sm font-bold text-white hover:border-zem-gold">Login as Restaurant</button>
        </div>
        <p class="mt-4 text-xs text-zem-muted">Demo password: password. Staff account: staff@bolebistro.test.</p>
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
@endsection
