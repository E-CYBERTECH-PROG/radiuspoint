<?php

namespace App\Notifications\Channels;

use App\Services\SmsService;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $phone = $notifiable->phone ?? null;
        $tenant = $notifiable->tenant ?? null;

        if (! $phone || ! $tenant) {
            return;
        }

        app(SmsService::class)->send($phone, $notification->toSms($notifiable), $tenant);
    }
}
