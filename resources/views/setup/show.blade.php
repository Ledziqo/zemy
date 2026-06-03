<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>ZemTab Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#050505] text-white">
<main class="mx-auto grid min-h-screen max-w-3xl place-items-center px-5 py-10">
    <section class="w-full rounded-2xl border border-white/10 bg-white/[.04] p-6">
        <h1 class="text-2xl font-extrabold">ZemTab database setup</h1>
        <p class="mt-3 text-sm text-neutral-400">Use this when you cannot run server commands. It runs migrations, seeds demo accounts, and clears cached config/views.</p>

        <form method="post" action="/setup/run" class="mt-6">
            @csrf
            <button class="rounded-lg bg-[#ef233c] px-5 py-3 font-extrabold text-white">Run setup now</button>
            <a href="/login" class="ml-3 text-sm font-bold text-neutral-300">Back to login</a>
        </form>

        @isset($success)
            <div class="mt-6 rounded-lg border {{ $success ? 'border-green-500/40 bg-green-950/50' : 'border-red-500/40 bg-red-950/50' }} p-4">
                <p class="font-bold">{{ $success ? 'Setup completed.' : 'Setup failed.' }}</p>
                <pre class="mt-3 max-h-96 overflow-auto whitespace-pre-wrap text-xs text-neutral-200">{{ $output }}</pre>
            </div>
        @endisset
    </section>
</main>
</body>
</html>
