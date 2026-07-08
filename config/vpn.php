<?php

// Real production endpoint the ZTP provisioning script points routers at —
// see App\Http\Controllers\RouterController::provision() and
// App\Http\Controllers\NasProvisioningController::startup().
return [
    'public_ip' => env('VPN_SERVER_PUBLIC_IP'),
    'public_key' => env('VPN_SERVER_PUBLIC_KEY'),
];
