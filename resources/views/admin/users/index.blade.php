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
                <option value="{{ $restaurant->id }}">{{ $restaurant->name }}</option>
            @endforeach
        </select>
        <p class="text-sm text-zem-muted md:col-span-5">Add as many owner or staff users as the restaurant needs. Platform admins should use "No restaurant".</p>
        <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white md:col-span-5">Create user</button>
    </form>
</div>

<div class="grid gap-3">
    @foreach($restaurants as $restaurant)
        <details class="rounded-md border border-zem-border bg-zem-card p-4">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 font-display text-lg font-bold">
                <span>{{ $restaurant->name }}</span>
                <span class="rounded-full border border-zem-border bg-zem-soft px-3 py-1 text-xs font-bold text-zem-muted">{{ $restaurant->users_count }} user(s)</span>
            </summary>
            <div class="mt-3 grid gap-3 border-t border-zem-border pt-3">
            @forelse($restaurant->users as $user)
                @include('admin.users._card', ['user' => $user, 'restaurants' => $restaurants])
            @empty
                <p class="rounded-md border border-zem-border bg-zem-soft p-4 text-sm text-zem-muted">No users assigned to this restaurant yet.</p>
            @endforelse
            </div>
        </details>
    @endforeach
    @if($platformUsers->isNotEmpty())
        <details class="rounded-md border border-zem-border bg-zem-card p-4">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 font-display text-lg font-bold"><span>Platform users</span><span class="rounded-full border border-zem-border bg-zem-soft px-3 py-1 text-xs font-bold text-zem-muted">{{ $platformUsers->count() }} user(s)</span></summary>
            <div class="mt-3 grid gap-3 border-t border-zem-border pt-3">
                @foreach($platformUsers as $user)
                    @include('admin.users._card', ['user' => $user, 'restaurants' => $restaurants])
                @endforeach
            </div>
        </details>
    @endif
    @if($restaurants->isEmpty() && $platformUsers->isEmpty())
        <div class="rounded-md border border-zem-border bg-zem-card p-8 text-center"><div class="text-3xl">👥</div><p class="mt-2 text-zem-muted">No users yet. Create one above.</p></div>
    @endif
</div>
@endsection
