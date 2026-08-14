<?php

namespace App\Http\Controllers;

use App\Models\SmsTrigger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsTriggerController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'triggers' => 'array',
            'triggers.*.enabled' => 'nullable|boolean',
            'triggers.*.sms_template_id' => 'nullable|exists:sms_templates,id',
        ]);

        $tenantId = Auth::user()->tenant_id;
        $posted = $request->input('triggers', []);

        foreach (SmsTrigger::KEYS as $key) {
            $row = $posted[$key] ?? [];

            SmsTrigger::updateOrCreate(
                ['tenant_id' => $tenantId, 'trigger_key' => $key],
                [
                    'sms_template_id' => $row['sms_template_id'] ?? null,
                    'enabled' => ! empty($row['enabled']),
                ]
            );
        }

        return redirect()->route('sms.index', ['tab' => 'automation'])->with('success', 'Automation settings saved.');
    }
}
