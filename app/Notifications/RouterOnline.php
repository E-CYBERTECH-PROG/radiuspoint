<?php

namespace App\Notifications;

use App\Enums\AlertType;
use App\Models\NotificationPreference;
use App\Models\Router;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class RouterOnline extends Notification
{
    use Queueable;

    public function __construct(public Router $router)
    {
    }

    public function via(object $notifiable): array
    {
        return NotificationPreference::resolveChannelClasses(
            NotificationPreference::channelsFor($notifiable, AlertType::RouterOnline->value)
        );
    }

    public function toSms(object $notifiable): string
    {
        return "RadiusPoint: {$this->router->name} ({$this->router->ip_address}) is back online.";
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Router back online')
            ->body("{$this->router->name} ({$this->router->ip_address}) is responding again.")
            ->data(['url' => route('routers.show', $this->router)]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->router->name} is back online")
            ->greeting("Good news, {$notifiable->name}")
            ->line("Router \"{$this->router->name}\" ({$this->router->ip_address}) is responding again.")
            ->action('View Router', route('routers.show', $this->router));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'alert_type' => AlertType::RouterOnline->value,
            'router_id' => $this->router->id,
            'router_name' => $this->router->name,
            'message' => "{$this->router->name} is back online.",
            'url' => route('routers.show', $this->router),
        ];
    }
}
