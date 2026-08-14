<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantStatusChanged extends Notification
{
    use Queueable;

    public function __construct(private readonly string $status)
    {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->status === 'suspended') {
            return (new MailMessage)
                ->subject('Your RadiusPoint account has been suspended')
                ->greeting('Hello, '.$notifiable->name.'.')
                ->line('Your company account on RadiusPoint has been suspended by our platform team.')
                ->line('Please contact support if you believe this is a mistake.');
        }

        return (new MailMessage)
            ->subject('Your RadiusPoint account has been reactivated')
            ->greeting('Good news, '.$notifiable->name.'!')
            ->line('Your company account on RadiusPoint has been reactivated.')
            ->action('Log In', route('login'))
            ->line('You can now log in and continue managing your hotspot business.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
