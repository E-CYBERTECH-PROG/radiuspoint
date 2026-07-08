<?php

namespace App\Notifications;

use App\Enums\AlertType;
use App\Models\NotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class MpesaGatewayRecovered extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return NotificationPreference::resolveChannelClasses(
            NotificationPreference::channelsFor($notifiable, AlertType::MpesaGatewayRecovered->value)
        );
    }

    public function toSms(object $notifiable): string
    {
        return 'RadiusPoint: M-Pesa payments are working again.';
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('M-Pesa payments recovered')
            ->body('A payment just succeeded — M-Pesa is working again.')
            ->data(['url' => route('mpesa-settings.edit')]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('M-Pesa payments are working again')
            ->greeting("Good news, {$notifiable->name}")
            ->line('A payment just succeeded after the earlier run of failures — M-Pesa is working again.')
            ->action('View Transactions', route('transactions.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'alert_type' => AlertType::MpesaGatewayRecovered->value,
            'message' => 'M-Pesa payments are working again.',
            'url' => route('transactions.index'),
        ];
    }
}
