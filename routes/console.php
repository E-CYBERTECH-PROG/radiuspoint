<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// withoutOverlapping() prevents a slow run from overlapping the next tick

// Starts a voucher's validity clock when it first connects.
Schedule::command('vouchers:activate')->everyMinute()->withoutOverlapping();

// Backfills MAC/router info for customers activated before their first connection.
Schedule::command('hotspot:sync-connection-info')->everyMinute()->withoutOverlapping();

// Revokes RADIUS access for anyone past their expiry.
Schedule::command('users:expire-overdue')->everyMinute()->withoutOverlapping();

// Applies pending WireGuard peers and reloads FreeRADIUS NAS clients.
Schedule::command('router:reconcile-networking')->everyMinute()->withoutOverlapping();

// Refreshes status/last_seen for every router.
Schedule::command('router:health-check')->everyMinute()->withoutOverlapping();

// Pushes every Plan to every active router's MikroTik profile config.
Schedule::command('plan:reconcile')->everyMinute()->withoutOverlapping();

// Throttles customers over their plan's data cap; restores speed on renewal.
Schedule::command('fup:enforce')->everyMinute()->withoutOverlapping();

// Cleans up Transactions stuck at 'pending' from requests that never finished.
Schedule::command('transactions:reap-stale')->everyFiveMinutes()->withoutOverlapping();

// Bills each eligible tenant 3% commission on last month's revenue.
Schedule::command('billing:generate-invoices')->monthlyOn(1, '00:15')->withoutOverlapping();

// Reminds PPPoE customers 3 days before expiry, per tenant SMS trigger settings.
Schedule::command('sms:expiry-reminders')->dailyAt('08:00')->withoutOverlapping();
