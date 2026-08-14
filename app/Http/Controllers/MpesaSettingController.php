<?php

namespace App\Http\Controllers;

use App\Models\MpesaSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Manages two independent M-Pesa gateways per tenant (slot 1 = primary, slot 2 = backup) — see
 * PaymentPortalController::pay(), which tries slot 1 first and automatically falls back to slot
 * 2 if it fails to initiate, so a customer can still pay if one Till/Paybill has an issue.
 */
class MpesaSettingController extends Controller
{
    public function edit()
    {
        $primary = MpesaSetting::firstOrNew(['tenant_id' => Auth::user()->tenant_id, 'slot' => 1]);
        $backup = MpesaSetting::firstOrNew(['tenant_id' => Auth::user()->tenant_id, 'slot' => 2]);

        return view('mpesa.settings', compact('primary', 'backup'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'primary_gateway_type' => 'required|in:till,paybill,bank',
            'primary_shortcode' => 'nullable|string|max:255',
            'primary_consumer_key' => 'nullable|string|max:255',
            'primary_consumer_secret' => 'nullable|string|max:255',
            'primary_passkey' => 'nullable|string|max:255',
            'primary_environment' => 'required|in:sandbox,production',
            'primary_is_active' => 'nullable|boolean',

            'backup_gateway_type' => 'nullable|in:till,paybill,bank',
            'backup_shortcode' => 'nullable|string|max:255',
            'backup_consumer_key' => 'nullable|string|max:255',
            'backup_consumer_secret' => 'nullable|string|max:255',
            'backup_passkey' => 'nullable|string|max:255',
            'backup_environment' => 'nullable|in:sandbox,production',
            'backup_is_active' => 'nullable|boolean',
        ]);

        $this->saveSlot(1, 'primary', $request);
        $this->saveSlot(2, 'backup', $request);

        return redirect()->route('mpesa-settings.edit')->with('success', 'M-Pesa settings saved.');
    }

    private function saveSlot(int $slot, string $prefix, Request $request): void
    {
        $setting = MpesaSetting::firstOrNew(['tenant_id' => Auth::user()->tenant_id, 'slot' => $slot]);

        $setting->tenant_id = Auth::user()->tenant_id;
        $setting->slot = $slot;
        $setting->gateway_type = $request->input("{$prefix}_gateway_type") ?? $setting->gateway_type ?? 'till';
        $setting->shortcode = $request->input("{$prefix}_shortcode");
        $setting->environment = $request->input("{$prefix}_environment") ?? $setting->environment ?? 'sandbox';
        $setting->is_active = $request->boolean("{$prefix}_is_active");

        if ($request->filled("{$prefix}_consumer_key")) {
            $setting->consumer_key = $request->input("{$prefix}_consumer_key");
        }
        if ($request->filled("{$prefix}_consumer_secret")) {
            $setting->consumer_secret = $request->input("{$prefix}_consumer_secret");
        }
        if ($request->filled("{$prefix}_passkey")) {
            $setting->passkey = $request->input("{$prefix}_passkey");
        }

        // Slot 2 (backup) is entirely optional — don't create a blank, "active but unconfigured"
        // row just because the primary form was submitted. Only persist it once something's
        // actually been filled in.
        if ($slot === 2 && ! $setting->exists && ! $request->filled('backup_shortcode') && ! $request->filled('backup_consumer_key')) {
            return;
        }

        $setting->save();
    }
}
