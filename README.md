# ZemTab

ZemTab is a Laravel MVP for QR menus, table ordering, service requests, and payment-ready restaurant operations.

Positioning: QR Menu & Table Ordering for Ethiopian Restaurants.

Tagline: Scan. Order. Pay.

## Tech Stack

- Laravel 12
- MySQL
- Blade templates
- Tailwind CSS via CDN for MVP simplicity
- Alpine.js via CDN for the customer cart
- Laravel session authentication

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create a MySQL database named `zemtab`, then update `.env` if your database username or password is different.

```bash
php artisan migrate --seed
php artisan serve
```

Open `http://127.0.0.1:8000`.

## Test URLs

- Landing page: `/`
- Demo customer menu: `/r/bole-bistro/table/7`
- Restaurant dashboard: `/restaurant/dashboard`
- Admin dashboard: `/admin/dashboard`

## Test Logins

- Admin: `admin@zemtab.test` / `password`
- Restaurant owner: `owner@bolebistro.test` / `password`
- Staff: `staff@bolebistro.test` / `password`

## Seeded Demo Data

The seeder creates Bole Bistro Demo in Bole, Addis Ababa with tables 1 through 10, menu categories, sample Ethiopian-market menu items, an owner, staff member, admin user, and a sample Pro subscription.

## MVP Features

- Premium public landing page with demo request form
- Demo request storage and admin review workflow
- Public QR menu URL format: `/r/{restaurant_slug}/table/{table_number}`
- Mobile-first customer ordering page
- Client-side cart with server-side validation
- Order creation with notes, quantities, totals, and manual payment method
- Call waiter and request bill service requests
- Restaurant dashboard with overview, orders, menu items, categories, tables, service requests, and settings
- Admin dashboard with restaurants, users, demo requests, subscriptions, and orders overview
- Role checks for admin and restaurant users
- Payment-ready `payments` table and TODO hooks for future gateway integrations

## Payment Structure

No live payment gateway is integrated in the MVP. Orders can store these payment methods:

- `cashier`
- `cash`
- `telebirr_manual`
- `bank_transfer_manual`
- `other_mobile_money`

Payment statuses are available as `unpaid`, `pending`, `paid`, `failed`, and `refunded`.

## Future Features

- Chapa payment integration
- SantimPay integration
- Telebirr/manual verification
- Real-time orders with Laravel Reverb or Pusher
- Kitchen display screen
- Multi-branch restaurants
- Customer feedback
- Loyalty system
- Coupons
- Analytics
- WhatsApp notifications
- SMS notifications
- Receipt printing
- POS integration
- Amharic language support
- Restaurant theme customization

## Notes

QR code image generation is intentionally left as an MVP TODO on the Tables / QR Codes page. The page currently shows the correct scan URL for each table, and a package such as `simplesoftwareio/simple-qrcode` can be added later for printable QR downloads.
