<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\SmsMessage;
use App\Services\RadiusSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    public function index()
    {
        $plans = Plan::where('tenant_id', Auth::user()->tenant_id)->where('type', 'hotspot')->get();

        return view('vouchers.index', compact('plans'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'mode' => 'required|in:single,batch',
            'quantity' => 'required_if:mode,batch|nullable|integer|min:1|max:21',
            'username' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $plan = Plan::where('tenant_id', Auth::user()->tenant_id)->where('type', 'hotspot')->findOrFail($request->plan_id);
        $quantity = $request->mode === 'batch' ? (int) $request->quantity : 1;

        $vouchers = [];

        for ($i = 0; $i < $quantity; $i++) {
            $code = $request->mode === 'single' && $request->filled('username')
                ? $request->username
                : Str::upper(Str::random(8));

            // Vouchers start "unused" with no expiry — the clock only starts once the
            // customer actually redeems the code and connects for the first time
            // (see App\Console\Commands\ActivateUsedVouchers).
            HotspotUser::create([
                'tenant_id' => Auth::user()->tenant_id,
                'phone_number' => $code,
                'current_plan_id' => $plan->id,
                'status' => 'unused',
                'expires_at' => null,
            ]);

            // Credentials must exist now so the voucher can actually authenticate on
            // the captive portal — but no Expiration is set yet, since the validity
            // window hasn't started.
            RadiusSyncService::sync($code, $code, $plan->speed_limit);

            $vouchers[] = [
                'code' => $code,
                'plan_name' => $plan->name,
                'price' => $plan->price,
                'validity' => "{$plan->duration_value} " . ucfirst($plan->duration_unit),
            ];
        }

        if ($request->filled('phone') && count($vouchers) === 1) {
            SmsMessage::create([
                'tenant_id' => Auth::user()->tenant_id,
                'phone_number' => $request->phone,
                'message' => "Your {$plan->name} voucher code is: {$vouchers[0]['code']}",
                'status' => 'queued',
                'initiator' => Auth::user()->name,
            ]);
        }

        return redirect()->route('vouchers.print')->with('vouchers', $vouchers);
    }

    public function print()
    {
        $vouchers = session('vouchers', []);

        if (empty($vouchers)) {
            return redirect()->route('vouchers.index');
        }

        return view('vouchers.print', compact('vouchers'));
    }
}
