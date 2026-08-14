<?php

namespace App\Notifications;

use App\Models\Router;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * The confirmation code required to decommission a router (see
 * RouterController::requestDecommission()). Always sent via database + push, ignoring the
 * tenant's general notification-channel preferences — a security code that could be silently
 * muted defeats the point of requiring it.
 */
class RouterDecommissionCode extends Notification
{
    use Queueable;

    public function __construct(public Router $router, public string $code)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Router removal code')
            ->body("Code {$this->code} to remove \"{$this->router->name}\". Expires in 5 minutes.")
            ->data(['url' => route('routers.show', $this->router)]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'alert_type' => 'router_decommission_code',
            'router_id' => $this->router->id,
            'router_name' => $this->router->name,
            'message' => "Removal code for \"{$this->router->name}\": {$this->code} (expires in 5 minutes).",
            'url' => route('routers.show', $this->router),
        ];
    }
}
