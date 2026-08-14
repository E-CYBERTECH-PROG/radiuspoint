<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantImported extends Notification
{
    use Queueable;

    public function __construct(private readonly string $temporaryPassword)
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
        return (new MailMessage)
            ->subject('Your RadiusPoint account has been created')
            ->greeting('Welcome, '.$notifiable->name.'!')
            ->line('An account has been created for your company on RadiusPoint by our platform team.')
            ->line('Your temporary password is: '.$this->temporaryPassword)
            ->action('Log In', route('login'))
            ->line('Please log in and change your password as soon as possible.');
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
