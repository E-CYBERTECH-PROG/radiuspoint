<?php

namespace App\Http\Controllers;

use App\Models\MpesaSetting;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentPortalController extends Controller
{
    private function tenantScoped(string $modelClass, int $tenantId)
    {
        return $modelClass::withoutGlobalScope('tenant')->where('tenant_id', $tenantId);
    }

    public function show(Request $request, Router $router)
    {
        $tenant = Tenant::find($router->tenant_id);
        $plans = $this->tenantScoped(Plan::class, $router->tenant_id)->where('type', 'hotspot')->get();

        return view('portal.show', [
            'router' => $router,
            'tenant' => $tenant,
            'plans' => $plans,
            'mac' => $request->query('mac'),
        ]);
    }

    public function pay(Request $request, Router $router)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'phone' => ['required', 'string', 'regex:/^(?:254|0)7\d{8}$/'],
            'mac' => 'nullable|string|max:255',
        ]);

        $plan = $this->tenantScoped(Plan::class, $router->tenant_id)->where('type', 'hotspot')->findOrFail($request->plan_id);

        $phone = $request->phone;
        if (str_starts_with($phone, '0')) {
            $phone = '254'.substr($phone, 1);
        }

        $transaction = Transaction::withoutGlobalScope('tenant')->create([
            'tenant_id' => $router->tenant_id,
            'customer_name' => $phone,
            'phone_number' => $phone,
            'package_name' => $plan->name,
            'amount' => $plan->price,
            'payment_method' => 'M-Pesa STK',
            'status' => 'pending',
            'plan_id' => $plan->id,
        ]);

        $settings = MpesaSetting::withoutGlobalScope('tenant')->where('tenant_id', $router->tenant_id)->first();

        if (! $settings || ! $settings->is_active) {
            $transaction->update(['status' => 'failed']);

            return response()->json(['error' => 'This network has not enabled M-Pesa payments yet.'], 422);
        }

        $mpesa = new MpesaService($settings);
        $response = $mpesa->stkPush(
            $phone,
            (float) $plan->price,
            'RadiusPoint-'.$transaction->id,
            $plan->name,
            route('api.mpesa.callback')
        );

        if (isset($response['CheckoutRequestID'])) {
            $transaction->update([
                'checkout_request_id' => $response['CheckoutRequestID'],
                'merchant_request_id' => $response['MerchantRequestID'] ?? null,
            ]);
        } else {
            Log::warning('STK push failed to initiate', $response);
            $transaction->update(['status' => 'failed']);

            return response()->json(['error' => 'Could not start the M-Pesa payment. Please try again.'], 422);
        }

        return response()->json(['transaction_id' => $transaction->id]);
    }

    public function status(Router $router, Transaction $transaction)
    {
        $transaction = Transaction::withoutGlobalScope('tenant')
            ->where('id', $transaction->id)
            ->where('tenant_id', $router->tenant_id)
            ->firstOrFail();

        $hotspotUser = $transaction->hotspot_user_id
            ? \App\Models\HotspotUser::withoutGlobalScope('tenant')->find($transaction->hotspot_user_id)
            : null;

        $password = $hotspotUser
            ? \Illuminate\Support\Facades\DB::table('radcheck')
                ->where('username', $hotspotUser->phone_number)
                ->where('attribute', 'Cleartext-Password')
                ->value('value')
            : null;

        return response()->json([
            'status' => $transaction->status,
            'username' => $hotspotUser?->phone_number,
            'password' => $password,
        ]);
    }
}
