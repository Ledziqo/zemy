# Guest menu improvements

Photo menus retain two columns on phones, with square photos, tighter card spacing, full details on tap, and readable text-only menus. The first two photos load eagerly; later photos load lazily and use existing optimized WebP files where available. Search and category filters share the same matching logic in ordering and browsing-only menus, with a reset action for empty results.

The cart limits each item to 50, persists item notes, shows service charge and VAT separately, and displays the final total on the cart dock. The server continues to calculate authoritative prices and totals. A submission guard prevents repeated clicks; existing request IDs protect retries.

Guest services remain fixed above the phone safe area. Requests show a dismissible confirmation, disable repeated taps for 60 seconds, and are also deduplicated on the server for the same table/type while pending during that interval. Normal form submissions remain available without JavaScript.

Order receipts retain an order ID in the URL and show item notes and actual stored totals. Status updates run every 15 seconds while the receipt is visible and online, stop on paid/completed/cancelled, and stop after repeated failures. The status endpoint requires the matching secret visit cookie and scopes the order to the venue, table, and visit. It returns only status, has a rate limit, and is never stored by the service worker. Normal menu browsing still does not query guest order history.

Menu-only accounts retain browsing and item details without ordering, cart, payment, or service request controls. Dialogs support Escape, keyboard focus trapping and restoration; controls have visible focus states and mobile touch sizes. Existing English, Amharic, Arabic, and Chinese language choices remain supported, with translations for the new primary controls.

Validation: `php -d extension=pdo_sqlite -d extension=gd tests/guest-experience.php`, `node tests/guest-experience.cjs`, menu-mode and security regressions, asset build/checks. Browser checks use the isolated localhost fixture, including order submission, live preparing status, receipt reload, menu-only details, 320px layout, and Arabic RTL.
