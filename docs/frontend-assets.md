# Frontend assets

Install with `npm ci`, then run `npm run build` and `npm run check:assets`.
Commit `package-lock.json` and generated `public/assets` files alongside source
changes. The PHP host needs no Node runtime. No deployment is performed by these
commands. `npm run watch` rebuilds CSS during local development.

Tailwind 3.4.17 and Alpine 3.14.9 are pinned. Alpine's upstream minified browser
build and license are copied without transformation. Asset URLs use content hashes
for cache invalidation through `components.frontend-assets`.
The Alpine npm package omits its license file, so the checked-in copy comes from
https://github.com/alpinejs/alpine/blob/v3.14.9/LICENSE.md. Licenses for its bundled
Vue helpers are also copied from the locked npm packages.

All Blade views (including inline JavaScript/Alpine expressions), application PHP,
resource JavaScript, and Laravel pagination views are scanned. Keep utility names
as complete literal strings in conditionals; do not construct names by joining
color or size fragments. Rebuild after **all** concurrent template edits finish.
`check:assets` verifies dynamic class mutations, important visibility overrides,
theme variables, Alpine bytes, and absence of runtime CDN/config references.

`layouts/app` validates the restaurant hex accent and supplies both the existing
`--zem-accent` hex value and `--zem-accent-rgb` channels. Tailwind uses the RGB
channels so opacity variants work. Dashboard variables (`--zem-bg`, `--zem-card`,
`--zem-text`, `--zem-muted`, `--zem-border`, `--zem-soft`) continue to be supplied
by its existing light/dark style block. Profile selection has its original palette
under `data-zem-palette="profile"`. QR setup pack print rules remain in its view.

## Handoff for the dashboard layout owner

In `resources/views/layouts/dashboard.blade.php`, replace the Tailwind CDN script,
the Alpine CDN script, and the entire inline `tailwind.config` script with:

```blade
@include('components.frontend-assets', ['alpine' => true])
```

Keep the theme initialization script, font link, and all existing inline styles,
including `:root`, `.dark`, and animation rules. No asset edits are needed inside
the child restaurant/admin dashboard or orders templates. The asset work does not
edit these owned templates. Until the layout owner applies this replacement,
`check:assets` deliberately reports that remaining runtime reference.
