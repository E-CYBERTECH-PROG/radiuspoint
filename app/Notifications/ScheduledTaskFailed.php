<?php

namespace App\Notifications;

use App\Enums\AlertType;
use App\Models\NotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class ScheduledTaskFailed extends Notification
{
    use Queueable;

    public function __construct(public string $command, public string $error)
    {
    }

    public function via(object $notifiable): array
    {
        return NotificationPreference::resolveChannelClasses(
            NotificationPreference::channelsFor($notifiable, AlertType::ScheduledTaskFailed->value)
        );
    }

    public function toSms(object $notifiable): string
    {
        return "RadiusPoint alert: scheduled task \"{$this->command}\" failed.";
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Scheduled task failed')
            ->body("\"{$this->command}\" threw an error and did not complete.");
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Scheduled task \"{$this->command}\" failed")
            ->greeting("Heads up, {$notifiable->name}")
            ->line("The scheduled task \"{$this->command}\" threw an error and did not complete:")
            ->line($this->error)
            ->line('Check storage/logs for the full trace.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'alert_type' => AlertType::ScheduledTaskFailed->value,
            'command' => $this->command,
            'message' => "Scheduled task \"{$this->command}\" failed: {$this->error}",
        ];
    }
}
