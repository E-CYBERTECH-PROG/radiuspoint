<?php

namespace App\Notifications;

use App\Enums\AlertType;
use App\Models\NotificationPreference;
use App\Models\Router;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class RouterCriticalLog extends Notification
{
    use Queueable;

    public function __construct(public Router $router, public string $logMessage)
    {
    }

    public function via(object $notifiable): array
    {
        return NotificationPreference::resolveChannelClasses(
            NotificationPreference::channelsFor($notifiable, AlertType::RouterCriticalLog->value)
        );
    }

    public function toSms(object $notifiable): string
    {
        return "RadiusPoint alert: {$this->router->name} logged: " . \Illuminate\Support\Str::limit($this->logMessage, 100);
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Router critical log entry')
            ->body("{$this->router->name}: " . \Illuminate\Support\Str::limit($this->logMessage, 120))
            ->data(['url' => route('routers.monitor', $this->router)]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->router->name} reported a critical log entry")
            ->greeting("Heads up, {$notifiable->name}")
            ->line("Router \"{$this->router->name}\" ({$this->router->ip_address}) logged:")
            ->line($this->logMessage)
            ->action('View Router', route('routers.monitor', $this->router));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'alert_type' => AlertType::RouterCriticalLog->value,
            'router_id' => $this->router->id,
            'router_name' => $this->router->name,
            'message' => "{$this->router->name}: {$this->logMessage}",
            'url' => route('routers.monitor', $this->router),
        ];
    }
}
