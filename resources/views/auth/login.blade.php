@extends('layouts.app', ['title' => 'Login - ZemTab'])

@section('content')
<main class="grid min-h-screen place-items-center bg-[radial-gradient(circle_at_top,rgba(239,35,60,.25),transparent_35%),#050505] px-5 py-10">
    <form method="post" action="{{ route('login.store') }}" class="w-full max-w-md rounded-2xl border border-white/10 bg-white/[.04] p-6 shadow-2xl backdrop-blur">
        @csrf
        <a href="{{ route('home') }}" class="inline-flex items-center"><img src="{{ asset('logo/zemtab-full-transparent-dark.png') }}" alt="ZemTab" class="h-16 w-auto"></a>
        <h1 class="mt-8 font-display text-2xl font-bold">Sign in</h1>
        <p class="mt-2 text-sm text-zem-muted">Access your restaurant or SaaS admin dashboard.</p>
        <div class="mt-6 space-y-4">
            <input name="email" type="email" value="{{ old('email') }}" required autofocus placeholder="Email" class="w-full rounded-lg border border-white/10 bg-black px-4 py-3 outline-none focus:border-zem-gold">
            <input name="password" type="password" required placeholder="Password" class="w-full rounded-lg border border-white/10 bg-black px-4 py-3 outline-none focus:border-zem-gold">
            <label class="flex items-center gap-2 text-sm text-zem-muted"><input type="checkbox" name="remember" value="1"> Remember me</label>
            <button class="w-full rounded-lg bg-zem-gold py-3 font-extrabold text-white transition hover:bg-red-700">Login</button>
        </div>
        <p class="mt-5 text-xs text-zem-muted">Demo: admin@zemtab.test, owner@bolebistro.test, staff@bolebistro.test / password</p>
    </form>
</main>
@endsection
