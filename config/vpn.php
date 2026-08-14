<?php

// Real production endpoint the ZTP provisioning script points routers at —
// see App\Http\Controllers\RouterController::provision() and
// App\Http\Controllers\NasProvisioningController::startup().
return [
    'public_ip' => env('VPN_SERVER_PUBLIC_IP'),
    'public_key' => env('VPN_SERVER_PUBLIC_KEY'),

    // The server's own address inside the WireGuard tunnel (wg0), not the public-facing
    // public_ip above. RADIUS/CoA traffic must target this — FreeRADIUS only binds to the
    // tunnel interface.
    'server_vpn_ip' => env('VPN_SERVER_IP', '10.0.0.1'),
];
