<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Starts a voucher's validity clock the moment it actually connects for the first time.
Schedule::command('vouchers:activate')->everyMinute();

// Revokes RADIUS access for anyone (voucher or regular customer) past their expiry.
Schedule::command('users:expire-overdue')->everyMinute();

// Applies pending WireGuard peers / reloads FreeRADIUS's NAS clients. Runs the
// privileged half of router provisioning — this rides the existing root crontab
// entry (`php artisan schedule:run`), so the web-facing app process itself never
// needs any elevated system privileges.
Schedule::command('router:reconcile-networking')->everyMinute();

// Keeps status/last_seen fresh for every router without a human needing to click "Test
// Connection" — feeds the NOC overview page's DB-only snapshot.
Schedule::command('router:health-check')->everyMinute();

// Pushes every Plan to every active router's MikroTik profile config. Runs on its own schedule
// rather than inline on save, so a slow/offline router can never block or fail a Plan save —
// and it keeps retrying automatically until it succeeds, no manual re-sync ever needed.
Schedule::command('plan:reconcile')->everyMinute();

// Throttles customers over their plan's data cap; restores full speed on renewal.
Schedule::command('fup:enforce')->everyMinute();
