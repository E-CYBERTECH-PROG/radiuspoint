<?php

// Real production endpoint the ZTP provisioning script points routers at —
// see App\Http\Controllers\RouterController::provision() and
// App\Http\Controllers\NasProvisioningController::startup().
return [
    'public_ip' => env('VPN_SERVER_PUBLIC_IP'),
    'public_key' => env('VPN_SERVER_PUBLIC_KEY'),

    // The server's own address *inside* the WireGuard tunnel (wg0), as opposed to public_ip
    // above (the internet-facing address routers dial to establish that tunnel in the first
    // place). Once the tunnel is up, RADIUS/CoA traffic must target this address — FreeRADIUS
    // only binds to the tunnel interface, not the public one — see
    // project_radius_firewall_and_timezone_fixes memory for the real router this was confirmed
    // broken/fixed against.
    'server_vpn_ip' => env('VPN_SERVER_IP', '10.0.0.1'),
];
