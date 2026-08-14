<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Tenant-wide branding shown on every one of the tenant's captive portals (support contact,
 * location), plus timezone (applied per-request by ApplyTenantTimezone) and currency symbol —
 * distinct from CaptivePortal, which holds per-router customization (logo, color, notice board).
 */
class CompanySettingController extends Controller
{
    /**
     * A short curated list rather than all ~400 IANA zones — this is an ISP billing app
     * operating in East Africa, not a global scheduling tool.
     */
    public const TIMEZONES = [
        'Africa/Nairobi' => 'Africa/Nairobi (EAT, UTC+3)',
        'Africa/Kampala' => 'Africa/Kampala (EAT, UTC+3)',
        'Africa/Dar_es_Salaam' => 'Africa/Dar es Salaam (EAT, UTC+3)',
        'Africa/Kigali' => 'Africa/Kigali (CAT, UTC+2)',
        'Africa/Lagos' => 'Africa/Lagos (WAT, UTC+1)',
        'Africa/Johannesburg' => 'Africa/Johannesburg (SAST, UTC+2)',
        'Africa/Cairo' => 'Africa/Cairo (EET, UTC+2)',
        'UTC' => 'UTC',
    ];

    public const CURRENCIES = ['KES', 'UGX', 'TZS', 'RWF', 'NGN', 'ZAR', 'GHS', 'USD'];

    public function edit()
    {
        $tenant = Tenant::findOrFail(Auth::user()->tenant_id);

        return view('company.settings', [
            'tenant' => $tenant,
            'timezones' => self::TIMEZONES,
            'currencies' => self::CURRENCIES,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'support_phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'timezone' => 'required|in:'.implode(',', array_keys(self::TIMEZONES)),
            'currency_symbol' => 'required|string|max:10',
        ]);

        $tenant = Tenant::findOrFail(Auth::user()->tenant_id);
        $tenant->update([
            'support_phone' => $request->support_phone,
            'location' => $request->location,
            'timezone' => $request->timezone,
            'currency_symbol' => $request->currency_symbol,
        ]);

        return redirect()->route('company-settings.edit')->with('success', 'Company settings saved.');
    }
}
