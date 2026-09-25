# RadiusPoint

Multi-tenant billing and management platform for ISPs running MikroTik RouterOS hotspot and PPPoE networks.

## What it does

- **Router onboarding** — one pasted bootstrap script sets up the VPN tunnel (WireGuard on RouterOS v7, L2TP/IPsec on v6), API access, and RADIUS; hotspot/PPPoE roles are then assigned per port.
- **Customers** — hotspot (M-Pesa self-service and vouchers) and PPPoE subscribers, authenticated through FreeRADIUS with per-package speed, burst, data cap, and fair-usage limits.
- **Captive portal** — self-hosted hotspot pages pushed to each router, themed per router.
- **Billing** — M-Pesa STK push, wallet adjustments, manual recharge audit trail, receipts, tenant invoicing.
- **Operations** — live router monitoring, fleet status, reports, SMS notifications, tickets, and leads.

## Stack

Laravel 12 (PHP 8.4), MySQL, Redis (cache, sessions, queue), FreeRADIUS, Tabler UI.

## Development

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
npm run build
php artisan test
```

Scheduled jobs (router health, RADIUS/plan reconciliation, expiry, fair usage) run from `php artisan schedule:run` every minute; queued work needs a running `php artisan queue:work`.
