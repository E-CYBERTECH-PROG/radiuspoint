<?php

// Color tokens for the router-hosted hotspot skin (public/hotspot/*.html + assets/css/style.css),
// keyed by the same template names selectable on a router's Captive Portal card. Deliberately a
// separate, smaller set of solid-color tokens from resources/views/captive-portal/partials/
// _styles.blade.php's — that one targets modern Chrome rendering our own /captive/{token} page;
// this one has to render correctly in the OS's captive-portal detection webview, which can be a
// much more constrained/older engine. No color-mix(), no gradients-in-variables, solid values only.
return [
    'light-lumen' => [
        'brand' => '#2563eb',
        'card-bg' => 'rgba(255, 255, 255, .95)',
        'card-border' => 'rgba(37, 99, 235, .15)',
        'text' => '#0f172a',
        'text-muted' => '#64748b',
    ],
    'crystal' => [
        'brand' => '#38bdf8',
        'card-bg' => 'rgba(15, 23, 42, .72)',
        'card-border' => 'rgba(255, 255, 255, .15)',
        'text' => '#f1f5f9',
        'text-muted' => '#94a3b8',
    ],
    'grid' => [
        'brand' => '#111827',
        'card-bg' => 'rgba(255, 255, 255, .97)',
        'card-border' => '#111827',
        'text' => '#111827',
        'text-muted' => '#52525b',
    ],
    'package' => [
        'brand' => '#2563eb',
        'card-bg' => 'rgba(238, 242, 255, .93)',
        'card-border' => 'rgba(37, 99, 235, .12)',
        'text' => '#0f172a',
        'text-muted' => '#64748b',
    ],
    'raw' => [
        'brand' => '#111827',
        'card-bg' => '#ffffff',
        'card-border' => '#111827',
        'text' => '#09090b',
        'text-muted' => '#52525b',
    ],
    'cyberpunk' => [
        'brand' => '#d946ef',
        'card-bg' => 'rgba(20, 8, 40, .75)',
        'card-border' => 'rgba(217, 70, 239, .45)',
        'text' => '#f5f3ff',
        'text-muted' => '#a78bfa',
    ],
    'lipa' => [
        'brand' => '#00a651',
        'card-bg' => 'rgba(20, 28, 22, .75)',
        'card-border' => 'rgba(0, 166, 81, .35)',
        'text' => '#f3f7f4',
        'text-muted' => '#9db3a5',
    ],
];
