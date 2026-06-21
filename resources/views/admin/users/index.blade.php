@extends('layouts.dashboard', ['heading' => 'Users'])

@section('content')
<div class="mb-6 rounded-md border border-zem-border bg-zem-card p-4">
    <h2 class="mb-3 font-display text-lg font-bold">Add new user</h2>
    <form method="post" action="{{ route('admin.users.store') }}" class="grid gap-3 md:grid-cols-5">
        @csrf
        <input name="name" required placeholder="Name" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <input name="email" type="email" required placeholder="Email" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <input name="password" type="password" required placeholder="Password" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
        <select name="role" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
            <option>restaurant_owner</option>
            <option>staff</option>
            <option>admin</option>
        </select>
        <select name="restaurant_id" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2">
            <option value="">No restaurant</option>
            @foreach($restaurants as $restaurant)
                <option value="{{ $restaurant->id }}" @disabled($restaurant->users_count > 0)>{{ $restaurant->name }}{{ $restaurant->users_count > 0 ? ' - account exists' : '' }}</option>
            @endforeach
        </select>
        <p class="text-sm text-zem-muted md:col-span-5">Restaurant logins are limited to one account per restaurant. Admin users should use "No restaurant".</p>
        <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white md:col-span-5">Create user</button>
    </form>
</div>

<div class="grid gap-3">
    @forelse($users as $user)
        @php($roleColors = ['admin' => 'bg-zem-gold/20 text-zem-gold border-zem-gold/40', 'restaurant_owner' => 'bg-blue-100 text-blue-700 border-blue-300', 'staff' => 'bg-gray-100 text-gray-600 border-gray-300'])
        <div class="rounded-md border border-zem-border bg-zem-card p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-display text-lg font-bold">{{ $user->name }}</h3>
                        <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $roleColors[$user->role] ?? 'border-zem-border text-zem-muted' }}">{{ $user->role }}</span>
                    </div>
                    <p class="mt-1 text-sm text-zem-muted">{{ $user->email }} - {{ $user->restaurant->name ?? 'Platform' }}</p>
                </div>
            </div>
            <details class="mt-3 rounded-md border border-zem-border bg-zem-bg p-3">
                <summary class="cursor-pointer text-sm font-bold text-zem-cream">Edit user</summary>
                <form method="post" action="{{ route('admin.users.update', $user) }}" class="mt-3 grid gap-3 md:grid-cols-4">
                    @csrf @method('PATCH')
                    <input name="name" value="{{ $user->name }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <input name="email" type="email" value="{{ $user->email }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <input name="password" type="password" placeholder="New password (leave blank to keep)" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    <select name="role" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                        @foreach(['admin','restaurant_owner','staff'] as $role)
                            <option value="{{ $role }}" @selected($user->role===$role)>{{ $role }}</option>
                        @endforeach
                    </select>
                    <select name="restaurant_id" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                        <option value="">No restaurant</option>
                        @foreach($restaurants as $restaurant)
                            @php($belongsToCurrentUser = $user->restaurant_id === $restaurant->id)
                            <option value="{{ $restaurant->id }}" @selected($belongsToCurrentUser) @disabled($restaurant->users_count > 0 && ! $belongsToCurrentUser)>{{ $restaurant->name }}{{ $restaurant->users_count > 0 && ! $belongsToCurrentUser ? ' - account exists' : '' }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Save</button>
                </form>
                <form method="post" action="{{ route('admin.users.destroy', $user) }}" class="mt-3" onsubmit="return confirm('Delete user {{ $user->name }}? This cannot be undone.');">
                    @csrf @method('DELETE')
                    <button class="w-full rounded-md border border-red-300 bg-red-50 px-4 py-2 text-sm font-bold text-red-700 transition hover:border-red-500 hover:bg-red-100">Delete user</button>
                </form>
            </details>
        </div>
    @empty
        <div class="rounded-md border border-zem-border bg-zem-card p-8 text-center">
            <div class="text-3xl mb-2">👥</div>
            <p class="text-zem-muted">No users yet. Create one above.</p>
        </div>
    @endforelse
</div>
@endsection
