<?php

use App\Models\User;
use App\Notifications\ScheduledTaskFailed;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Stringable;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// withoutOverlapping() prevents a slow run from overlapping the next tick. Its default lock
// expiry is 24 HOURS (Laravel's own default, not something set here) — if a run ever crashes or
// gets killed without releasing the lock (OOM, server restart, a hung external process), every
// later tick silently no-ops for up to a day with zero error output to trigger onFailure below.
// This bit a real router: router:reconcile-networking's lock got stuck, so a newly-provisioned
// router's WireGuard peer was never applied server-side even though the router's own script had
// completed fine — RadiusPoint just never saw it, because the tunnel never came up. Every
// command below now gets an explicit expiry sized to a realistic worst case instead.

// A failed scheduled command otherwise fails silently — this alerts platform admins with
// whatever the command printed, so it doesn't take a customer complaint to notice.
$alertOnFailure = fn (string $command) => function (Stringable $output) use ($command) {
    Notification::send(
        User::where('is_platform_admin', true)->get(),
        new ScheduledTaskFailed($command, trim((string) $output) ?: 'exited with a non-zero status.')
    );
};

// Starts a voucher's validity clock when it first connects.
Schedule::command('vouchers:activate')->everyMinute()->withoutOverlapping(5)
    ->onFailureWithOutput($alertOnFailure('vouchers:activate'));

// Backfills MAC/router info for customers activated before their first connection.
Schedule::command('hotspot:sync-connection-info')->everyMinute()->withoutOverlapping(5)
    ->onFailureWithOutput($alertOnFailure('hotspot:sync-connection-info'));

// Revokes RADIUS access for anyone past their expiry.
Schedule::command('users:expire-overdue')->everyMinute()->withoutOverlapping(10)
    ->onFailureWithOutput($alertOnFailure('users:expire-overdue'));

// Applies pending WireGuard peers and reloads FreeRADIUS NAS clients.
Schedule::command('router:reconcile-networking')->everyMinute()->withoutOverlapping(5)
    ->onFailureWithOutput($alertOnFailure('router:reconcile-networking'));

// Refreshes status/last_seen for every router.
Schedule::command('router:health-check')->everyMinute()->withoutOverlapping(10)
    ->onFailureWithOutput($alertOnFailure('router:health-check'));

// Pushes every Plan to every active router's MikroTik profile config.
Schedule::command('plan:reconcile')->everyMinute()->withoutOverlapping(10)
    ->onFailureWithOutput($alertOnFailure('plan:reconcile'));

// Throttles customers over their plan's data cap; restores speed on renewal.
Schedule::command('fup:enforce')->everyMinute()->withoutOverlapping(5)
    ->onFailureWithOutput($alertOnFailure('fup:enforce'));

// Cleans up Transactions stuck at 'pending' from requests that never finished.
Schedule::command('transactions:reap-stale')->everyFiveMinutes()->withoutOverlapping(5)
    ->onFailureWithOutput($alertOnFailure('transactions:reap-stale'));

// Bills each eligible tenant 3% commission on last month's revenue.
Schedule::command('billing:generate-invoices')->monthlyOn(1, '00:15')->withoutOverlapping(30)
    ->onFailureWithOutput($alertOnFailure('billing:generate-invoices'));

// Reminds PPPoE customers 3 days before expiry, per tenant SMS trigger settings.
Schedule::command('sms:expiry-reminders')->dailyAt('08:00')->withoutOverlapping(15)
    ->onFailureWithOutput($alertOnFailure('sms:expiry-reminders'));
