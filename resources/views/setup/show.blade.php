<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>ZemTab Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F7F7F4] text-[#18181B]">
<main class="mx-auto grid min-h-screen max-w-3xl place-items-center px-5 py-10">
    <section class="w-full rounded-2xl border border-[#DADAD6] bg-white p-6">
        <h1 class="text-2xl font-extrabold">ZemTab database setup & updates</h1>
        <p class="mt-3 text-sm text-neutral-400">Use this when you cannot run server commands. It applies new database updates, refreshes setup data, and clears cached files.</p>

        <form method="post" action="/setup/run" class="mt-6">
            @csrf
            @php($db = $db ?? ['host' => 'srv2081.hstgr.io', 'database' => 'u409029281_zemtab', 'username' => 'u409029281_zemtab'])
            <div class="mb-5 grid gap-3 md:grid-cols-2">
                <label class="grid gap-1 text-sm">
                    <span class="font-bold">DB Host</span>
                    <input name="db_host" value="{{ old('db_host', $db['host'] ?? 'srv2081.hstgr.io') }}" placeholder="srv2081.hstgr.io" class="rounded-lg border border-[#DADAD6] bg-white px-3 py-2">
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-bold">DB Name</span>
                    <input name="db_database" value="{{ old('db_database', $db['database'] ?? 'u409029281_zemtab') }}" class="rounded-lg border border-[#DADAD6] bg-white px-3 py-2">
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-bold">DB Username</span>
                    <input name="db_username" value="{{ old('db_username', $db['username'] ?? 'u409029281_zemtab') }}" class="rounded-lg border border-[#DADAD6] bg-white px-3 py-2">
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-bold">DB Password</span>
                    <input name="db_password" type="password" placeholder="Enter exact database password" class="rounded-lg border border-[#DADAD6] bg-white px-3 py-2">
                </label>
            </div>
            <button class="rounded-lg bg-[#E85D5D] px-5 py-3 font-extrabold text-white">Run setup / updates now</button>
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

