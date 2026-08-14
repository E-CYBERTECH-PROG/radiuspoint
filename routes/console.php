<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// withoutOverlapping() on every entry below: none of these previously had it, and every one
// either connects to live routers or scans every customer — exactly the kind of work this
// session already proved can run long (confirmed StreamException timeouts, PHP-FPM workers
// SIGKILLed after 48-140s under load). Without this guard, a single slow run doesn't just run
// late — the *next* minute's run starts on top of it, and if that piles up a few cycles deep, the
// server ends up with a growing stack of stuck php artisan processes all fighting over the same
// routers/DB rows. withoutOverlapping() uses a cache lock so a still-running instance simply
// causes the next tick to skip, not queue up. expiresAt(1440) is a hard safety net if a lock file
// is ever left behind by a killed process (e.g. OOM) — matches Laravel's own recommended pattern.

// Starts a voucher's validity clock the moment it actually connects for the first time.
Schedule::command('vouchers:activate')->everyMinute()->withoutOverlapping();

// Backfills MAC/router for customers activated (e.g. by M-Pesa payment) before they'd ever
// actually connected, so there was nothing to read at activation time.
Schedule::command('hotspot:sync-connection-info')->everyMinute()->withoutOverlapping();

// Revokes RADIUS access for anyone (voucher or regular customer) past their expiry.
Schedule::command('users:expire-overdue')->everyMinute()->withoutOverlapping();

// Applies pending WireGuard peers / reloads FreeRADIUS's NAS clients. Runs the
// privileged half of router provisioning — this rides the existing root crontab
// entry (`php artisan schedule:run`), so the web-facing app process itself never
// needs any elevated system privileges.
Schedule::command('router:reconcile-networking')->everyMinute()->withoutOverlapping();

// Keeps status/last_seen fresh for every router without a human needing to click "Test
// Connection" — feeds the NOC overview page's DB-only snapshot.
Schedule::command('router:health-check')->everyMinute()->withoutOverlapping();

// Pushes every Plan to every active router's MikroTik profile config. Runs on its own schedule
// rather than inline on save, so a slow/offline router can never block or fail a Plan save —
// and it keeps retrying automatically until it succeeds, no manual re-sync ever needed.
Schedule::command('plan:reconcile')->everyMinute()->withoutOverlapping();

// Throttles customers over their plan's data cap; restores full speed on renewal.
Schedule::command('fup:enforce')->everyMinute()->withoutOverlapping();

// Cleans up Transactions orphaned at 'pending' by a request that never finished (a killed
// PHP-FPM worker, a crash) — a real M-Pesa STK prompt expires within ~90s, so nothing legitimate
// is ever still "pending" 10 minutes later.
Schedule::command('transactions:reap-stale')->everyFiveMinutes()->withoutOverlapping();

// Bills each eligible tenant 3% commission on last month's revenue. Runs once on the 1st —
// idempotent (unique period constraint), so a missed/late run just catches up next time.
Schedule::command('billing:generate-invoices')->monthlyOn(1, '00:15')->withoutOverlapping();

// Reminds PPPoE customers 3 days before expiry, if the tenant has that trigger enabled (see
// SmsTrigger::KEYS). A lightweight DB scan, not a router call — dailyAt is enough, no need for
// everyMinute like the router-facing commands above.
Schedule::command('sms:expiry-reminders')->dailyAt('08:00')->withoutOverlapping();
