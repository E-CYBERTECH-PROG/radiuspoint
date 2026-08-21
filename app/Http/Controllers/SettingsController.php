<?php

namespace App\Http\Controllers;

use App\Models\MpesaSetting;
use App\Models\SmsSetting;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Single "Account" settings page unifying what used to be three separate
 * screens (Profile, M-Pesa Settings, Company Settings), tab-switched via
 * ?tab=. Profile's Appearance picker and account deletion stay on the
 * standalone profile.edit page (linked from the General tab) rather than
 * being duplicated here.
 */
class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $tenant = Tenant::findOrFail($user->tenant_id);

        $mpesaPrimary = MpesaSetting::firstOrNew(['tenant_id' => $tenant->id, 'slot' => 1]);
        $mpesaBackup = MpesaSetting::firstOrNew(['tenant_id' => $tenant->id, 'slot' => 2]);
        $smsSetting = SmsSetting::firstOrNew(['tenant_id' => $tenant->id]);

        $tab = in_array($request->get('tab'), self::TABS, true) ? $request->get('tab') : 'general';

        return view('settings.index', [
            'tab' => $tab,
            'user' => $user,
            'tenant' => $tenant,
            'mpesaPrimary' => $mpesaPrimary,
            'mpesaBackup' => $mpesaBackup,
            'smsSetting' => $smsSetting,
            'timezones' => CompanySettingController::TIMEZONES,
            'currencies' => CompanySettingController::CURRENCIES,
        ]);
    }

    public const TABS = [
        'general', 'licence', 'payment-gateway', 'sms-gateway',
        'email-gateway', 'notes-template', 'change-password', '2fa',
    ];

    public function updateGeneral(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'required|string|max:255',
            'isp_prefix' => 'nullable|string|max:20',
            'subdomain' => 'nullable|string|max:100|unique:tenants,subdomain,'.$request->user()->tenant_id,
        ]);

        $user = Auth::user();
        $user->name = trim($request->first_name.' '.$request->last_name);
        $user->phone = $request->phone;
        if ($user->email !== $request->email) {
            $user->email = $request->email;
            $user->email_verified_at = null;
        }
        $user->save();

        Tenant::findOrFail($user->tenant_id)->update([
            'company_name' => $request->company_name,
            'isp_prefix' => $request->isp_prefix,
            'subdomain' => $request->subdomain,
        ]);

        return redirect()->route('account.index', ['tab' => 'general'])->with('success', 'Account details saved.');
    }
}
