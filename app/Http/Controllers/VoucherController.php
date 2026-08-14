<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\Plan;
use App\Services\RadiusSyncService;
use App\Services\SmsTriggerService;
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

            // Starts "unused" with no expiry; the clock starts on first redemption
            // (see App\Console\Commands\ActivateUsedVouchers).
            HotspotUser::create([
                'tenant_id' => Auth::user()->tenant_id,
                'phone_number' => $code,
                'current_plan_id' => $plan->id,
                'status' => 'unused',
                'expires_at' => null,
            ]);

            // RADIUS credentials must exist now to authenticate on the captive portal,
            // but no expiration is set until the validity window starts.
            RadiusSyncService::sync($code, $code, $plan->speed_limit);

            $vouchers[] = [
                'code' => $code,
                'plan_name' => $plan->name,
                'price' => $plan->price,
                'validity' => "{$plan->duration_value} " . ucfirst($plan->duration_unit),
            ];
        }

        if ($request->filled('phone') && count($vouchers) === 1) {
            SmsTriggerService::fire(
                Auth::user()->tenant_id, 'hotspot_voucher_created', $request->phone,
                ['name' => $request->phone, 'plan' => $plan->name, 'code' => $vouchers[0]['code']],
                fallbackMessage: "Your {$plan->name} voucher code is: {$vouchers[0]['code']}"
            );
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
