@php($roleLabels = ['owner_manager' => 'Owner/Manager', 'cashier' => 'Cashier', 'kitchen' => 'Kitchen'])
<div class="rounded-md border border-zem-border bg-zem-bg p-3">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="font-semibold">{{ $profile->name }}</span>
            <span class="rounded-full border border-zem-border bg-zem-soft px-2.5 py-1 text-xs font-bold text-zem-muted">{{ $roleLabels[$profile->role] ?? $profile->role }}</span>
            <span class="rounded-full border px-2.5 py-1 text-xs font-bold {{ $profile->is_active ? 'border-green-300 bg-green-100 text-green-700' : 'border-red-300 bg-red-100 text-red-700' }}">{{ $profile->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        <details>
            <summary class="cursor-pointer text-sm font-bold text-zem-cream">Edit profile</summary>
            <form method="post" action="{{ route('admin.restaurants.staff-profiles.update', [$restaurant, $profile]) }}" class="mt-3 grid gap-2 md:grid-cols-5">
                @csrf @method('PATCH')
                <input name="name" value="{{ $profile->name }}" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                <select name="role" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                    @if($profile->role === 'owner_manager')<option value="owner_manager">Owner/Manager</option>@else<option value="cashier" @selected($profile->role === 'cashier')>Cashier</option><option value="kitchen" @selected($profile->role === 'kitchen')>Kitchen</option>@endif
                </select>
                <input name="password" type="password" minlength="4" placeholder="New password optional" class="rounded-md border border-zem-border bg-zem-card px-3 py-2">
                <label class="flex items-center gap-2 text-sm"><input name="is_active" type="checkbox" value="1" @checked($profile->is_active) class="accent-zem-gold"> Active</label>
                <button class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Save</button>
            </form>
            <form method="post" action="{{ route('admin.restaurants.staff-profiles.destroy', [$restaurant, $profile]) }}" class="mt-2" onsubmit="return confirm('Delete {{ $profile->name }}?');">
                @csrf @method('DELETE')
                <button class="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm font-bold text-red-700">Delete profile</button>
            </form>
        </details>
    </div>
</div>
