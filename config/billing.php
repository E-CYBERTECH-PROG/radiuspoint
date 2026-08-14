<?php

// Platform-level commission billing (see App\Console\Commands\GenerateTenantInvoices,
// App\Http\Middleware\EnsureTenantSubscribed) — not to be confused with a tenant's own
// per-customer M-Pesa settings (App\Models\MpesaSetting), which are unrelated.
return [
    // RadiusPoint's own internal tenant row (id=2, "RadiusPoint Platform" — the container for
    // platform-admin staff accounts). A platform admin's own M-Pesa Settings page configures
    // *this* tenant's MpesaSetting, which is what collects tenant commission payments — reusing
    // existing per-tenant M-Pesa infrastructure rather than building a parallel system.
    'platform_tenant_id' => env('BILLING_PLATFORM_TENANT_ID', 2),

    'commission_rate' => 0.03,

    'grace_days' => 2,
];
