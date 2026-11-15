# ZemTab visual refresh

Implemented locally on 15 November 2026. Not pushed or deployed.

## Updated experience

- Shared dashboard shell for administrators, owners/managers, cashiers and kitchen staff: consistent surfaces, spacing, typography, navigation icons, active states, page descriptions, controls and footer.
- Menu workspace introduction, shortcuts, accessible creation-field names and a clearer add-item section; consistent analytics metrics.
- Login with a branded desktop introduction and a compact mobile form; refined staff profile picker.
- Landing page with a product-led hero, illustrative guest/staff preview, direct interactive-demo action and separate digital-menu/full-service options. Existing pricing, demo request and contact flows remain available. Removed the unsubstantiated aggregate-rating metadata with the old hero.
- Consistent guest catalog and ordering presentation, confirmation screen, hotel guide and information editor. Venue accent colours and logos remain driven by existing settings.
- Mobile dashboard navigation now opens reliably; Escape closes it. Landing mobile navigation closes after choosing a destination.
- Fixed an existing driver pickup form error found during the audit: missing Alpine state and an invalid reference to button options. Selected items and quantities now populate correctly, with duplicate selections merged within the existing 50-item quantity limit.

## Verification

- **61 browser visual checks passed:** desktop, 375px mobile, selected 320px views, dark mode, desktop/mobile RTL, all eight admin navigation destinations, operational screens, information/QR tools, photo/text guest views and profile-password login. No page-level horizontal overflow, captured JavaScript errors or server 500s.
- **20 end-to-end workflow checks passed:** authentication, real menu editing, tenant restrictions, information editing/logo uploads/toggle, QR designs and print preview, account creation, selected-venue imports, seven-day billing suspension/restoration, server-priced orders, duplicate retries, completion/payment and service requests.
- QR PDF quality, QR editor interactions, menu mode browser checks, product demo checks, 159 security assertions, 82 menu-mode assertions, 12 Menu QR page checks, 42 subscription checks, 15 profile-free checks and 14 print-pack storage checks passed.
- All 58 Blade templates compiled and passed PHP syntax checks. Frontend build, dynamic asset checks and diff whitespace checks passed.

Browser runs use disposable SQLite fixtures and the local Laravel HTTP server. These results are not a new production performance or load test.

## Preview files

- [Dashboard](dashboard-desktop.png)
- [Landing page hero](landing-desktop.png)
- [Login](login-desktop.png)
- [Mobile menu workspace](menu-mobile.png)
- [Mobile hotel guide](guest-guide-mobile.png)
- [Visual check results](visual-checks.json)
- [Workflow check results](workflow-checks.json)

## Maintenance

`public/assets/interface.css` contains shared app/dashboard/auth/guest styling. `public/assets/marketing.css` loads only on the landing and interactive-demo routes. Asset URLs include content hashes. The existing Tailwind pipeline remains `npm run build`; compiled `public/assets/app.css` is updated.

Reusable line icons are in `resources/views/components/ui-icon.blade.php`; the illustrative landing preview is in `resources/views/components/landing-product-preview.blade.php`. No new JavaScript package or network font dependency was added.

Re-run the browser audit with `tests/e2e-seed.php`, `tests/e2e-server.php` and `tests/visual-refresh-browser.cjs`, using the isolated setup described in the earlier launch-readiness report. The seed now includes disposable photo-menu and staff-profile accounts and clears only its private file-cache store so frozen-clock rate limits do not accumulate between runs.
