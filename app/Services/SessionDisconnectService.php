<?php

namespace App\Services;

use App\Models\Router;
use Throwable;

/**
 * Force-ends a customer's live session on the router by username — looks up the matching active
 * session (RouterOS has no "disconnect by username" endpoint, only by internal .id) then removes
 * it. Previously duplicated inline in EnforceFairUsage; also backs the manual "Force Disconnect"
 * button on a customer's own edit page (HotspotUserController/PppoeUserController) and the
 * per-session disconnect buttons on the router's Live Monitor page share the same underlying
 * RouterOS calls, just already have the session .id in hand from their own active-list poll.
 */
class SessionDisconnectService
{
    /**
     * @param  string  $activeEndpoint  RouterOS endpoint listing active sessions (e.g. /ip/hotspot/active/print)
     * @param  string  $activeUserField  which field on an active-session row holds the username
     * @param  string  $removeEndpoint  RouterOS endpoint to end a specific active session
     */
    public static function disconnect(Router $router, string $activeEndpoint, string $activeUserField, string $removeEndpoint, string $username): bool
    {
        try {
            $api = new MikrotikApiService();
            if (! $api->connect($router->ip_address, $router->api_username, $router->api_password)) {
                return false;
            }

            $session = collect($api->query($activeEndpoint))->first(fn ($row) => $row[$activeUserField] === $username);
            if (! $session) {
                return false;
            }

            $api->query($removeEndpoint, ['.id' => $session['.id']]);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
