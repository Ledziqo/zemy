# Calendar-month subscription billing

- `ends_at` is the next payment due date, always the first of a month.
- Access remains open on the 1st–7th, inclusive, with a prominent dashboard payment warning. The warning includes remaining grace days and the lock date, and appears for all staff roles during grace.
- Unpaid accounts lose dashboard, guest menu, guest information and ordering access on the 8th. Already-open workboard polling also observes the deadline. Existing inactive/revoked/manual account restrictions still take priority.
- Verified payment renews by calendar month and restores access immediately. An early payment extends the existing future due date; a late payment renews from the current calendar month. Missed suspended months are not added. There are no new penalty or fee calculations.
- Arbitrary day adjustments were replaced by calendar-month renewal choices in Admin → Payments. Compatible old forms may submit positive multiples of 30 days, interpreted as calendar months; other day adjustments are rejected.
- Admin → Subscriptions and account creation/editing normalize non-first dates forward to the next first. New subscriptions without an end date default to the first following their start month.
- Date boundaries use the app's existing `Africa/Addis_Ababa` timezone (GMT+3).

## Deployment

Run `php artisan migrate --force` with the deployment. Migration `2026_11_15_000000_align_subscription_billing_to_month_start` aligns existing dates forward without shortening periods. Previously undated existing subscriptions receive the upcoming first (or the first following a future start date). Prices, statuses and account access overrides are preserved. This data alignment is forward-only; historical arbitrary dates cannot be inferred on rollback.

These changes do not activate or alter live accounts until deployed. They do not create subscriptions for accounts that have none.

## Validation

`php -d extension=pdo_sqlite tests/monthly-billing.php` runs against an in-memory database and exercises 130 assertions: date normalization and migration, leap years, exact grace boundaries, real admin create/edit/renewal controllers, no extra fees during suspension, rendered admin controls, prominent warning content and dashboard/public middleware on the 1st, 7th and 8th.

The existing `tests/subscription-grace.php`, account, menu-mode and security checks also remain applicable.
