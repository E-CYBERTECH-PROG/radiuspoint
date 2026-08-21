<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Tenant-wide settings shown on every captive portal (support contact, location), plus
 * timezone and currency. Distinct from CaptivePortal, which holds per-router customization.
 */
class CompanySettingController extends Controller
{
    /**
     * Curated East Africa timezones, not the full IANA list.
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
        // Now embedded in the unified Account page instead of its own screen.
        return redirect()->route('account.index', ['tab' => 'general']);
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

        return redirect()->route('account.index', ['tab' => 'general'])->with('success', 'Company settings saved.');
    }
}
