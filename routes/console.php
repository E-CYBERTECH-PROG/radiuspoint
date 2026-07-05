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
