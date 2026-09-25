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

    // RouterOS v6 has no WireGuard client, so those routers tunnel in over L2TP/IPsec
    // (strongswan + xl2tpd) instead — see Router::buildProvisioningScript(). This PSK secures
    // only the outer IPsec transport, shared by every v6 router; per-router identity/auth still
    // comes from that router's own unique api_username/api_password over PPP CHAP, same as the
    // unique wg_private_key each router gets on the WireGuard side.
    'l2tp_ipsec_psk' => env('VPN_L2TP_IPSEC_PSK'),
];
