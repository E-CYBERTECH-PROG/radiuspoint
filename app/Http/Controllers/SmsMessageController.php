<?php

namespace App\Http\Controllers;

use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Models\SmsSetting;
use App\Models\SmsTrigger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsMessageController extends Controller
{
    public function index(Request $request)
    {
        $search = $this->searchTerm($request);

        $messages = SmsMessage::where('tenant_id', Auth::user()->tenant_id)
            ->when($search, fn ($q) => $q->where('phone_number', 'like', "%{$search}%"))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();
        $templates = SmsTemplate::where('tenant_id', Auth::user()->tenant_id)->get();
        $setting = SmsSetting::firstOrNew(['tenant_id' => Auth::user()->tenant_id]);
        $triggers = SmsTrigger::where('tenant_id', Auth::user()->tenant_id)->get()->keyBy('trigger_key');

        return view('sms.index', compact('messages', 'templates', 'setting', 'triggers'));
    }

    public function store(Request $request)
    {
        // Named bag so a failed submission from the customer detail page's own Send SMS
        // offcanvas doesn't also pop open that page's unrelated "Edit Info" offcanvas, which
        // autoshows on any *default*-bag error.
        $request->validateWithBag('sms', [
            'phone_number' => 'required|string|max:20',
            'message' => 'required|string|max:1000',
            'sms_template_id' => 'nullable|exists:sms_templates,id',
        ]);

        SmsMessage::create([
            'tenant_id' => Auth::user()->tenant_id,
            'phone_number' => $request->phone_number,
            'message' => $request->message,
            'status' => 'queued',
            'initiator' => Auth::user()->name,
            'sms_template_id' => $request->sms_template_id,
        ]);

        // Lets the customer detail page's own "Send SMS" offcanvas send the user back there
        // instead of the SMS log — only a same-app relative path is ever honored, so this
        // can't be turned into an open redirect.
        $redirectTo = $request->input('redirect_to');
        $target = ($redirectTo && str_starts_with($redirectTo, '/')) ? $redirectTo : route('sms.index');

        return redirect()->to($target)->with('success', 'Message queued for sending.');
    }

    public function destroy(SmsMessage $sms)
    {
        $sms->delete();

        return redirect()->route('sms.index')->with('success', 'Message removed from log.');
    }
}
