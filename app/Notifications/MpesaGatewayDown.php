<?php

namespace App\Notifications;

use App\Enums\AlertType;
use App\Models\NotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class MpesaGatewayDown extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return NotificationPreference::resolveChannelClasses(
            NotificationPreference::channelsFor($notifiable, AlertType::MpesaGatewayDown->value)
        );
    }

    public function toSms(object $notifiable): string
    {
        return 'RadiusPoint alert: M-Pesa payments appear to be failing repeatedly. Customers may be unable to pay.';
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('M-Pesa payments failing')
            ->body('Several consecutive STK push attempts have failed — customers may be unable to pay.')
            ->data(['url' => route('mpesa-settings.edit')]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('M-Pesa payments appear to be failing')
            ->greeting("Heads up, {$notifiable->name}")
            ->line('Several consecutive M-Pesa STK push attempts have failed in a row — this often means Safaricom is experiencing an outage, or your M-Pesa credentials need attention.')
            ->line("You'll get another notice once payments start succeeding again.")
            ->action('Check M-Pesa Settings', route('mpesa-settings.edit'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'alert_type' => AlertType::MpesaGatewayDown->value,
            'message' => 'M-Pesa payments appear to be failing.',
            'url' => route('mpesa-settings.edit'),
        ];
    }
}
