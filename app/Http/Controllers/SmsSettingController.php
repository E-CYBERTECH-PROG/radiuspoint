<?php

namespace App\Http\Controllers;

use App\Models\SmsSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsSettingController extends Controller
{
    public function edit()
    {
        // Settings lives as a tab on the SMS Outbox page rather than its own view.
        return redirect()->route('sms.index', ['tab' => 'settings']);
    }

    public function update(Request $request)
    {
        $request->validate([
            'provider' => 'nullable|string|max:255',
            'sender_id' => 'nullable|string|max:20',
            'username' => 'nullable|string|max:255',
            'api_key' => 'nullable|string|max:255',
            'api_secret' => 'nullable|string|max:255',
        ]);

        $setting = SmsSetting::firstOrNew(['tenant_id' => Auth::user()->tenant_id]);

        $setting->provider = $request->provider;
        $setting->sender_id = $request->sender_id;
        $setting->username = $request->username;

        if ($request->filled('api_key')) {
            $setting->api_key = $request->api_key;
        }
        if ($request->filled('api_secret')) {
            $setting->api_secret = $request->api_secret;
        }

        $setting->tenant_id = Auth::user()->tenant_id;
        $setting->save();

        return redirect()->route('sms.index')->with('success', 'SMS settings saved.');
    }
}
