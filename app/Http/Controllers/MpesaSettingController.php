<?php

namespace App\Http\Controllers;

use App\Models\MpesaSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MpesaSettingController extends Controller
{
    public function edit()
    {
        $setting = MpesaSetting::firstOrNew(['tenant_id' => Auth::user()->tenant_id]);

        return view('mpesa.settings', compact('setting'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'gateway_type' => 'required|in:till,paybill,bank',
            'shortcode' => 'nullable|string|max:255',
            'consumer_key' => 'nullable|string|max:255',
            'consumer_secret' => 'nullable|string|max:255',
            'passkey' => 'nullable|string|max:255',
            'environment' => 'required|in:sandbox,production',
            'is_active' => 'nullable|boolean',
        ]);

        $setting = MpesaSetting::firstOrNew(['tenant_id' => Auth::user()->tenant_id]);

        $setting->gateway_type = $request->gateway_type;
        $setting->shortcode = $request->shortcode;
        $setting->environment = $request->environment;
        $setting->is_active = $request->boolean('is_active');

        if ($request->filled('consumer_key')) {
            $setting->consumer_key = $request->consumer_key;
        }
        if ($request->filled('consumer_secret')) {
            $setting->consumer_secret = $request->consumer_secret;
        }
        if ($request->filled('passkey')) {
            $setting->passkey = $request->passkey;
        }

        $setting->tenant_id = Auth::user()->tenant_id;
        $setting->save();

        return redirect()->route('mpesa-settings.edit')->with('success', 'M-Pesa settings saved.');
    }
}
