# ZemTab Production Release-Readiness Audit

Date: 2026-06-21  
Target: `https://zemtab.com`  
Verdict: **NO GO**

## Executive summary

ZemTab is not ready for a public launch. Core guest ordering and restaurant workflows work at low traffic, but the audit confirmed multiple release-blocking security, tenant-isolation, reliability, and capacity defects.

Most importantly:

- `/setup` is publicly accessible and its POST action can rewrite database credentials, migrate, reseed, and clear production caches.
- A cross-tenant probe allowed one restaurant to create a menu item referencing another restaurant's category.
- Five active-restaurant-equivalent Workboard polling produced `500` responses and p95 concurrent-batch latency of 4.18 seconds.
- Database-backed production routes remained `500` for more than 75 seconds after the load stopped.
- The public sitemap is invalid XML.
- The guest menu rate limit blocks a shared public IP after 60 menu requests per minute.

No further production stress was sent after the mandatory abort condition.

## Functional evidence

| Area | Result | Evidence |
|---|---:|---|
| Landing page | Pass | `200`; approximately 55 KB |
| Login page | Pass | `200`; support phone and Telegram links present |
| Owner login | Pass | Gina owner reached restaurant dashboard |
| Admin login | Pass | Admin reached admin dashboard |
| Role separation | Pass | Owner→admin and admin→restaurant routes returned `403` |
| CSRF | Pass | Missing-token service request returned `419` |
| Guest menu | Pass at low load | Gina tables 1–3 returned `200` before load test |
| Guest order | Pass | Tagged order ID 20 appeared on Workboard |
| Money calculation | Pass | 250 ETB item became 287.50 ETB with configured 15% charges; UI displayed 288 ETB |
| Cancellation under two minutes | Pass | Order ID 20 cancelled successfully |
| Cancellation after two minutes | Not completed | Test stopped when database-backed routes began returning `500` |
| Service request | Pass | Tagged request ID 13 appeared and completed through JSON endpoint |
| Temporary tenant lifecycle | Pass | Restaurant, owner, category, table, item, and public menu created; tenant deleted afterward |
| Tenant isolation | **Fail** | Temporary tenant created an item using Gina category ID 10; server returned `200` |
| Amharic landing | Pass (HTTP/source) | Locale cookie produced Amharic HTML and `lang="am"` |
| Theme/responsive visual QA | Limited | Interactive browser connection unavailable; source/templates compiled but screenshot-level QA not performed |
| Internal public crawl | Partial | No verified broken application links; external protocol-relative links were excluded from conclusions |
| Sitemap | **Fail** | XML parser failed on an unescaped ampersand in image caption |

Test artifacts left in Gina's history because no deletion interface exists:

- Cancelled tagged order ID 20.
- Completed tagged service request ID 13.

All temporary restaurants created by the audit were deleted.

## Capacity results

### Stage 1 — one active restaurant equivalent

Mixed menu, order, service-request, and two-Workboard polling workload for five minutes:

| Metric | Result |
|---|---:|
| Duration | 300 seconds |
| Requests | 245 |
| Errors | 0 |
| p50 | 472 ms |
| p95 | 1,080 ms |
| p99 | 1,666 ms |
| Result | Pass |

### Stage 5 — five active restaurant equivalents

Ten simultaneous Workboard polls every four seconds:

| Metric | Result |
|---|---:|
| Duration before abort | 56 seconds |
| Requests | 140 |
| `200` responses | 138 |
| `500` responses | 2 |
| Concurrent-batch p50 | 2,759 ms |
| Concurrent-batch p95 | 4,178 ms |
| Result | **Fail / mandatory abort** |

After the abort, static pages returned `200`, but Gina's menu and the database-backed sitemap continued returning `500` after more than 75 seconds. This strongly indicates a database/PHP-worker/connection bottleneck, although hosting metrics and server logs are required to identify the exact limiting resource.

Verified capacity is therefore only **one active restaurant equivalent** under the defined workload. Five is not supported. General availability must not launch on this evidence.

## Defects

### P0 — release blockers

1. **Public production setup control**
   - `/setup` returns `200` publicly.
   - `/setup/run` accepts database settings, writes `.env`, runs migrations and seeders, and clears caches.
   - Fix: remove public setup routes; permit deployment commands only from CLI. If an emergency web action remains, require admin authentication, reauthentication, an environment feature flag, and audit logging.

2. **Production fails at five active-restaurant-equivalent polling load**
   - Two `500` responses appeared within 56 seconds; database-backed routes did not recover promptly.
   - Fix: inspect production PHP/MySQL logs and limits, connection exhaustion, slow queries, CPU/RAM, disk I/O, and worker saturation before any further load test.

3. **Hard-coded production-capable credentials in source**
   - The seeder contains a named admin email and hard-coded password; demo accounts use `password`.
   - Fix: remove fixed credentials, use environment-provided one-time bootstrap credentials, rotate all possibly related live credentials, and consider repository-history cleanup.

### P1 — high severity

1. **Cross-tenant category association**
   - Menu-item validation uses global `exists:categories,id` rather than scoping the category to the authenticated restaurant.
   - Fix: validate with `Rule::exists(...)->where('restaurant_id', ...)` and add database-level tenant-consistency safeguards.

2. **Unsafe authentication and write rate limits**
   - Login, orders, service requests, demo requests, uploads, cancellation, and setup lack tailored throttles.
   - Fix: add per-IP/account/device limits, escalating login lockout, and abuse monitoring.

3. **Guest-menu shared-IP denial**
   - Exactly 60 menu requests returned `200`, then five returned `429`.
   - A busy venue on shared Wi-Fi can block its own guests.
   - Fix: redesign the limiter and cache public menu responses without weakening write-route protection.

4. **Payment proofs stored under public web paths**
   - Uploaded financial screenshots are stored in `public/uploads/payment-proofs` with guessable visit/timestamp-based names.
   - Fix: store privately, authorize access by restaurant, use random names, and serve through expiring/signed responses.

5. **No automated test suite or staging safety net**
   - No feature, unit, browser, security, or performance tests are present.
   - Fix: add isolated MySQL-backed CI, fixtures/factories, and a staging clone before production retesting.

### P2 — important improvements

- Sitemap XML does not escape `&`; search engines cannot parse it reliably.
- HSTS is missing; CSP only contains `upgrade-insecure-requests`.
- Tailwind, Alpine, and fonts are runtime CDN dependencies; Alpine uses a floating `3.x.x` version.
- Public HTML is `private, no-cache` and creates session cookies, preventing effective edge caching.
- Workboard polling performs repeated database work every four seconds; restaurant-access middleware also performs schema/subscription checks on every poll.
- File-backed sessions/cache limit horizontal scaling and increase disk contention.
- `Cache::flush()` for menu changes clears every tenant's cache.
- Poll queries need verified composite indexes such as `(restaurant_id, id)`; migration index failures are silently swallowed.
- Uploaded/replaced/deleted images are not reliably removed from disk, creating orphaned files.
- `www.zemtab.com` and apex both return full `200` pages rather than enforcing one canonical host.
- No operational health endpoint verifies database readiness; `/up` can remain healthy while database routes fail.

## What is good

- Core ordering, service-request, cancellation, and final-total calculations work at low load.
- CSRF protection and basic admin/owner role separation behaved correctly.
- Guest orders are associated with high-entropy visit tokens.
- Cancellation uses a server-side two-minute check and row locking.
- Owner/admin CRUD fundamentals and cleanup worked for a disposable tenant.
- Security headers include frame, MIME-sniffing, referrer, and permissions protections.
- The application consistently uses server-side validation in most write controllers.

## Required remediation order

1. Disable public setup immediately and rotate exposed/default credentials.
2. Investigate and restore database reliability; collect the stage-5 server metrics and logs.
3. Fix cross-tenant validation and audit every foreign-key/model-binding path.
4. Add login/write throttles and redesign guest-menu throttling.
5. Move payment proofs to private authorized storage.
6. Fix sitemap XML and canonical-host redirects.
7. Replace runtime CDN assets with pinned compiled assets and deploy stronger CSP/HSTS.
8. Move sessions/cache to database or Redis, remove schema checks from hot paths, optimize polling, and verify indexes.
9. Build automated functional/security tests and a staging load environment.
10. Repeat this audit from baseline; do not infer capacity from the current failed run.

## Launch decision

**NO GO.** Do not onboard paying restaurants or advertise general availability until every P0/P1 item is fixed and a repeated capacity test passes at the intended launch load with at least 50% headroom.

Given the current capacity evidence, even a small pilot should wait until the database-backed routes recover and the cause of the stage-5 failure is known.
