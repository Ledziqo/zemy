@extends('layouts.dashboard', ['heading' => 'Staff Profiles', 'eyebrow' => 'Manage cashier & kitchen accounts'])

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-6 rounded-lg border border-zem-border bg-zem-card p-5">
        <h2 class="font-display text-lg font-bold">Create New Profile</h2>
        <form method="post" action="{{ route('restaurant.staff-profiles.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
            @csrf
            <label class="grid gap-1 text-sm">
                <span class="font-bold">Name</span>
                <input name="name" required placeholder="e.g. Sara" class="rounded-lg border border-zem-border bg-white px-3 py-2">
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-bold">Role</span>
                <select name="role" class="rounded-lg border border-zem-border bg-white px-3 py-2">
                    <option value="cashier">Cashier</option>
                    <option value="kitchen">Kitchen</option>
                </select>
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-bold">Password</span>
                <input name="password" type="password" required placeholder="Profile password" class="rounded-lg border border-zem-border bg-white px-3 py-2">
            </label>
            <div class="flex items-end">
                <button class="rounded-lg bg-zem-gold px-5 py-2.5 text-sm font-bold text-white">Create Profile</button>
            </div>
        </form>
    </div>

    <div class="space-y-3">
        @foreach($profiles as $profile)
            <div class="rounded-lg border border-zem-border bg-zem-card p-5" x-data="{ editing: false }">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-white @if($profile->role === 'owner_manager') bg-zem-gold @elseif($profile->role === 'cashier') bg-blue-600 @else bg-green-600 @endif">
                            {{ strtoupper(substr($profile->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-bold">{{ $profile->name }}</p>
                            <p class="text-xs text-zem-muted">{{ $profile->roleLabel() }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        @if($profile->role !== 'owner_manager')
                            <button @click="editing = !editing" class="rounded-lg border border-zem-border px-3 py-2 text-sm font-bold text-zem-muted">Edit</button>
                            <form method="post" action="{{ route('restaurant.staff-profiles.destroy', $profile) }}" onsubmit="return confirm('Delete this profile?')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg border border-red-400 px-3 py-2 text-sm font-bold text-red-600">Delete</button>
                            </form>
                        @endif
                    </div>
                </div>

                @if($profile->role !== 'owner_manager')
                <form method="post" action="{{ route('restaurant.staff-profiles.update', $profile) }}" class="mt-4 grid gap-3 sm:grid-cols-2 hidden" :class="editing ? '!grid' : ''">
                    @csrf @method('PATCH')
                    <label class="grid gap-1 text-sm">
                        <span class="font-bold">Name</span>
                        <input name="name" value="{{ $profile->name }}" class="rounded-lg border border-zem-border bg-white px-3 py-2">
                    </label>
                    <label class="grid gap-1 text-sm">
                        <span class="font-bold">New Password (leave blank to keep)</span>
                        <input name="password" type="password" placeholder="New password" class="rounded-lg border border-zem-border bg-white px-3 py-2">
                    </label>
                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" {{ $profile->is_active ? 'checked' : '' }}>
                            <span>Active</span>
                        </label>
                    </div>
                    <div class="sm:col-span-2">
                        <button class="rounded-lg bg-zem-gold px-5 py-2.5 text-sm font-bold text-white">Save Changes</button>
                    </div>
                </form>
                @endif
            </div>
        @endforeach
    </div>
</div>
