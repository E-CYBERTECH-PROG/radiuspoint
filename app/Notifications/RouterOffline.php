<?php

namespace App\Notifications;

use App\Enums\AlertType;
use App\Models\NotificationPreference;
use App\Models\Router;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class RouterOffline extends Notification
{
    use Queueable;

    public function __construct(public Router $router)
    {
    }

    public function via(object $notifiable): array
    {
        return NotificationPreference::resolveChannelClasses(
            NotificationPreference::channelsFor($notifiable, AlertType::RouterOffline->value)
        );
    }

    public function toSms(object $notifiable): string
    {
        return "RadiusPoint alert: {$this->router->name} ({$this->router->ip_address}) went offline.";
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Router offline')
            ->body("{$this->router->name} ({$this->router->ip_address}) stopped responding.")
            ->data(['url' => route('routers.show', $this->router)]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->router->name} went offline")
            ->greeting("Heads up, {$notifiable->name}")
            ->line("Router \"{$this->router->name}\" ({$this->router->ip_address}) stopped responding.")
            ->action('View Router', route('routers.show', $this->router))
            ->line("You'll get another notice once it's back online.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'alert_type' => AlertType::RouterOffline->value,
            'router_id' => $this->router->id,
            'router_name' => $this->router->name,
            'message' => "{$this->router->name} went offline.",
            'url' => route('routers.show', $this->router),
        ];
    }
}
