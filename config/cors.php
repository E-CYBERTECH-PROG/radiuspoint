<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The router-hosted hotspot skin (public/hotspot/login.html, login2.html,
    | daraja.js) is served from the router's own IP (e.g. http://10.100.0.1)
    | and calls out to this backend via fetch() — that's cross-origin, so
    | these paths must be CORS-enabled or the browser blocks the response
    | even though the request itself succeeds (200 OK).
    |
    */

    'paths' => ['captive/*', 'portal/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
