<?php

// Catalog of MikroTik RouterBoard/switch models, used to render a port-count schematic
// and product photo when an admin adds a router. Discontinued-but-still-deployed models
// (e.g. RB2011) are listed without an 'image' key, falling back to a generic icon.
// "other" is the fallback for anything not listed.
return [
    // --- hAP series (home/office wireless routers) ---
    'hap_lite' => ['label' => 'hAP lite', 'ports' => 4, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1007_tm.webp'],
    'hap' => ['label' => 'hAP', 'ports' => 5, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1059_tm.webp'],
    'hap_ac_lite' => ['label' => 'hAP ac lite', 'ports' => 5, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1413_tm.webp'],
    'hap_ac' => ['label' => 'hAP ac', 'ports' => 5, 'sfp' => 1, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1169_tm.webp'],
    'hap_ac2' => ['label' => 'hAP ac²', 'ports' => 5, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1468_tm.webp'],
    'hap_ac3' => ['label' => 'hAP ac³', 'ports' => 5, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1975_tm.webp'],
    'hap_ax_lite' => ['label' => 'hAP ax lite', 'ports' => 4, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/2225_tm.webp'],
    'hap_ax2' => ['label' => 'hAP ax²', 'ports' => 5, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/2203_tm.webp'],
    'hap_ax3' => ['label' => 'hAP ax³', 'ports' => 5, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/2211_tm.webp'],
    'hap_ax_s' => ['label' => 'hAP ax S', 'ports' => 5, 'sfp' => 1, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/2502_tm.webp'],
    'hap_be_lite' => ['label' => 'hAP be lite', 'ports' => 5, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/2671_tm.webp'],
    'hap_be3_media' => ['label' => 'hAP be³ Media', 'ports' => 5, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/2618_tm.webp'],

    // --- wAP series (compact outdoor/indoor access points) ---
    'wap_ax' => ['label' => 'wAP ax', 'ports' => 1, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/2410_tm.webp'],
    'wap_r' => ['label' => 'wAP R', 'ports' => 1, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1313_tm.webp'],

    // --- hEX series (compact Gigabit routers) ---
    'hex_lite' => ['label' => 'hEX lite', 'ports' => 5, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1040_tm.webp'],
    'hex' => ['label' => 'hEX (RB750Gr3)', 'ports' => 5, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1405_tm.webp'],
    'hex_poe_lite' => ['label' => 'hEX PoE lite', 'ports' => 5, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1412_tm.webp'],
    'hex_s' => ['label' => 'hEX S', 'ports' => 5, 'sfp' => 1, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1539_tm.webp'],
    'hex_poe' => ['label' => 'hEX PoE', 'ports' => 5, 'sfp' => 1, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1219_tm.webp'],
    'hex_s_2025' => ['label' => 'hEX S (2025)', 'ports' => 5, 'sfp' => 1, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/2458_tm.webp'],

    // --- RB series (ISP-grade routers) ---
    'rb951' => ['label' => 'RB951Ui-2HnD', 'ports' => 5, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/902_tm.webp'],
    'rb2011' => ['label' => 'RB2011UiAS-2HnD (legacy)', 'ports' => 10, 'sfp' => 1],
    'rb3011' => ['label' => 'RB3011UiAS-RM', 'ports' => 10],
    'rb4011' => ['label' => 'RB4011iGS+RM', 'ports' => 10, 'sfp' => 1, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1633_tm.webp'],
    'rb5009' => ['label' => 'RB5009UG+S+IN', 'ports' => 8, 'sfp' => 1, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/2065_tm.webp'],
    'rb1100ahx4' => ['label' => 'RB1100AHx4', 'ports' => 13, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1344_tm.webp'],

    // --- CCR series (Cloud Core Routers) ---
    'ccr2004_16g' => ['label' => 'CCR2004-16G-2S+', 'ports' => 16, 'sfp' => 2, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/2563_tm.webp'],
    'ccr2004_12s' => ['label' => 'CCR2004-1G-12S+2XS', 'ports' => 1, 'sfp' => 12, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1935_tm.webp'],
    'ccr2116' => ['label' => 'CCR2116-12G-4S+', 'ports' => 12, 'sfp' => 4, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/2625_tm.webp'],
    'ccr2216' => ['label' => 'CCR2216-1G-12XS-2XQ', 'ports' => 1, 'sfp' => 12, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/2122_tm.webp'],

    // --- CRS series (Cloud Router Switches) ---
    'crs326_24' => ['label' => 'CRS326-24G-2S+', 'ports' => 24, 'sfp' => 2, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1301_tm.webp'],
    'crs328_24p' => ['label' => 'CRS328-24P-4S+RM', 'ports' => 24, 'sfp' => 4, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1493_tm.webp'],
    'crs309' => ['label' => 'CRS309-1G-8S+IN', 'ports' => 1, 'sfp' => 8, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1730_tm.webp'],
    'crs317' => ['label' => 'CRS317-1G-16S+RM', 'ports' => 1, 'sfp' => 16, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1324_tm.webp'],
    'crs354_48' => ['label' => 'CRS354-48G-4S+2Q+RM', 'ports' => 48, 'sfp' => 4, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1899_tm.webp'],
    'crs106' => ['label' => 'CRS106-1C-5S', 'ports' => 1, 'sfp' => 5],
    'crs112' => ['label' => 'CRS112-8P-4S-IN', 'ports' => 8, 'sfp' => 4],

    // --- CSS series (compact switches) ---
    'css610_8' => ['label' => 'CSS610-8G-2S+IN', 'ports' => 8, 'sfp' => 2, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1980_tm.webp'],
    'css326' => ['label' => 'CSS326-24G-2S+RM', 'ports' => 24, 'sfp' => 2, 'image' => 'https://cdn.mikrotik.com/web-assets/rb_images/1267_tm.webp'],

    // --- Misc ---
    'rb260gs' => ['label' => 'RB260GS', 'ports' => 5, 'sfp' => 1],

    'other' => ['label' => 'Other / Generic', 'ports' => 5],
];
