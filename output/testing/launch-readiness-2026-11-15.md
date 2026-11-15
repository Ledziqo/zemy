# ZemTab local end-to-end results — 15 November 2026

Result: PASS for the exercised local workflows. Changes remain local and have not been pushed or deployed.

## Real HTTP and browser run

20 checks passed using headless Microsoft Edge and the actual Laravel HTTP kernel, authentication, sessions, CSRF, middleware, controllers and rendered pages. The isolated SQLite database is tmp/e2e/test.sqlite. Test time is deliberately frozen to the payment boundary; no real customer account, payment or message was used.

- Profile-free owner login; menu-only access and guest catalog.
- Menu item creation and visibility; cross-hotel edit rejection.
- Settings and information-board editor, sections, ordering, public publication, logo upload/resize, feature toggle and direct URL restrictions.
- QR studio selection, menu-only wording, sync across designs, both-card print preview and independent sizing/orientation.
- Mobile menu and information board at 375px; saved screenshots visually reviewed.
- Access allowed through December 7 for a November 30 paid-through date, blocked December 8 on dashboard and guest menu/guide/order POST despite previously cached content.
- Admin payment verification restores owner and guest access immediately.
- Admin Database page, account creation, menu ZIP import (259 items) and information JSON import (33 sections and embedded logo), with tenant isolation.
- Full-service table/room previews; actual order submission, server-calculated total, duplicate retry, confirmation/preparation/serving/completion/payment and water request persistence.
- Reusing an order request ID from another guest receives HTTP 409 without creating a duplicate or returning 500.
- No captured browser JavaScript errors or server 500 responses.

Machine-readable run: ../../tmp/e2e/report.json
Screenshots: ../../tmp/e2e/menu-mobile.png, ../../tmp/e2e/guide-mobile.png, ../../tmp/e2e/print-layout.png

## Regression checks

366 numbered assertions passed across security (159), account creation (32), profile-free login (15), menu modes (82), Menu QR page (12), branding (6), print-pack storage (14), database monitor (4), and subscription boundaries/open workboard polling (42).

17 controller workflow checks passed, including seven payment methods, authorization and invalid transitions. Location imports, cashier reports, rate-limit isolation and asset checks passed. Browser regressions passed for menu modes, QR spacing, editor interactions, all ten visible editable QR layers, independent saved variants and PDF export. SVG logos remain vector artwork and PNG logos retain source resolution. Changed PHP syntax and git diff whitespace checks passed.

## Changes made during this pass

- Seven-day subscription grace applies consistently to dashboard and public service access; cached guest content does not bypass suspension. An already-open workboard cannot continue polling after its session grace deadline.
- Duplicate/reused order request IDs produce an existing order or a controlled conflict instead of an uncaught unique-constraint server error.
- Updated older test assumptions to match the current QR editor, palette, print layout, CSS serialization and removed/hidden layers. The PDF assertion now isolates the venue logo from intentionally rasterized shadows and QR branding.

## Limits before production sign-off

This is a local functional pass using SQLite, not a live Hostinger/MySQL deployment or load test. After deployment, smoke-test Nazra login, settings, both live QR destinations, import availability and payment restoration against production configuration. Scan a physical printed card on actual phones and confirm print scaling with the printer. Automated payment-provider settlement, email delivery, hosting/data protection and legal contract compliance are not certified by these tests. No concurrency stress/capacity claim is made.

## Re-run

Enable PHP pdo_sqlite and gd extensions. Seed with tests/e2e-seed.php, then serve tests/e2e-server.php on 127.0.0.1:8766 and run node tests/e2e-browser.cjs. QR_PLAYWRIGHT must point to the installed Playwright module. Reset tmp/e2e/clock.json to {"now":"2026-12-07 12:00:00"} before reseeding. This harness drops tables only in its explicitly checked private SQLite file. Other regression scripts are in tests/.
