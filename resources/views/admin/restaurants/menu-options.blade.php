<label class="grid gap-1 text-xs font-semibold text-zem-muted">Account features
    <select name="service_mode" required class="rounded-md border border-zem-border bg-zem-card px-3 py-2 text-sm">
        <option value="full" @selected(! $account?->isMenuOnly())>Full ordering system</option>
        <option value="menu_only" @selected($account?->isMenuOnly())>Digital menu only</option>
    </select>
    <span class="font-normal">Digital menu only provides menu, price, category, and QR editing. Full service adds ordering, workboard, and analytics.</span>
</label>
<label class="grid gap-1 text-xs font-semibold text-zem-muted">Menu appearance
    <select name="menu_photos_enabled" required class="rounded-md border border-zem-border bg-zem-card px-3 py-2 text-sm">
        <option value="1" @selected($account?->menuPhotosEnabled() ?? true)>With item photos</option>
        <option value="0" @selected($account && ! $account->menuPhotosEnabled())>Text only</option>
    </select>
    <span class="font-normal">Change either choice anytime. Saved menu items and photos are kept.</span>
</label>
