# Isolated launch verification

Run from the repository root (PHP 8.2+ with PDO SQLite and SimpleXML):

```powershell
php -d extension=pdo_sqlite tools/launch-check.php
php -d extension=pdo_sqlite tools/launch-check.php --setup-preview
php -d extension=pdo_sqlite -S 127.0.0.1:8091 -t public tools/preview-router.php
```

On this machine PHP is `C:/tools/php85/php.exe`; SQLite's DLL is in `C:/tools/php85/ext`.

The setup command prints the random local fixture password. Restaurant account: `aurora@launch.test`; admin: `admin@launch.test`. Select Owner/Manager, Cashier, or Kitchen after restaurant login, using the same fixture password. A second fictional tenant uses `birch@launch.test`. Guest entrypoint: `http://localhost:8091/r/launch-aurora/table/1`.

## Isolation

The shared bootstrap deliberately omits Laravel's environment-file loader and ignores cached app configuration and routes. Before providers boot, it replaces **all** database connections with the isolated SQLite connection and overrides sessions, cache, logs, compiled views, and filesystem disks. Neither `.env` nor the configured real database is opened. No production code is modified.

Fixture databases, random credentials, sessions, logs, and rendered HTML live under `storage/framework/testing/launch-checks` and `storage/framework/testing/launch-preview`. The check runner wraps mutations in a transaction and rolls them back. The browser fixture is independent and persistent. Do not publish these directories or credentials.

Schema initialization uses an explicit migration allowlist before seeding any rows. No June demo-account or July tenant/menu-data migrations execute. The two July migrations allowed are schema-only: kitchen-screen fields and menu image-source field. The June staff-profile migration sees an empty restaurants table, so its default-profile loop creates nothing; profiles are then seeded with random passwords. This is schema verification, not a production data migration rehearsal.

The preview binds to loopback, rejects unexpected Host and remote-address values, disables setup/PHP entrypoints, and serves only allowlisted public static assets. It uses normal account/profile authentication and CSRF protection. There is no authentication bypass. The CLI runner uses Laravel's testing CSRF exemption; browser CSRF was independently checked to return 419 for a tokenless login POST.

## Coverage and current findings

Latest completed run: **95 checks, zero failures** (2026-09-16). The runner exits nonzero on assertion failure and writes `launch-checks/results.json`. It saves rendered owner/cashier/kitchen dashboard and orders HTML, plus admin dashboard HTML, beside that report.

Coverage includes schema columns and SQLite foreign-key integrity; valid public sitemap XML; guest order pricing, inactive-category rejection, tenant isolation and cancellation ownership; role restrictions on menu/settings/admin; profile ownership, revocation, role changes and login/logout session behavior; kitchen confirmation/payment restrictions; cashier payment attribution; and a manual-order preparation/serving lifecycle. All four dashboards and owner settings/menu return HTTP 200.

The first run briefly encountered missing dashboard views while other agents replaced them; subsequent runs rendered successfully. Tests follow the current strict role middleware and order handlers. Rerun after workboard changes land.

Limitations: this does not validate MySQL-specific SQL, real payment providers, concurrent requests, JavaScript interactions, or visual layout. Cache is isolated and cleared between CLI requests, so cache-expiry behavior is not exercised. Browser visual QA uses the running preview. Assertions use real HTTP middleware and persisted database state, without PHPUnit.
