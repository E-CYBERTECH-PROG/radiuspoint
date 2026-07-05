<?php

namespace App\Http\Controllers;

use App\Models\SmsTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsTemplateController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
        ]);

        SmsTemplate::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $request->name,
            'body' => $request->body,
        ]);

        return redirect()->route('sms.index')->with('success', 'Template saved.');
    }

    public function update(Request $request, SmsTemplate $sms_template)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
        ]);

        $sms_template->update([
            'name' => $request->name,
            'body' => $request->body,
        ]);

        return redirect()->route('sms.index')->with('success', 'Template updated.');
    }

    public function destroy(SmsTemplate $sms_template)
    {
        $sms_template->delete();

        return redirect()->route('sms.index')->with('success', 'Template removed.');
    }
}
