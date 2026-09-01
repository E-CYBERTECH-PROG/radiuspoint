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
            'router_id' => $router->id,
        ]);

        // Try slot 1 (primary) first, then slot 2 (backup) if it fails to start.
        $gateways = MpesaSetting::withoutGlobalScope('tenant')
            ->where('tenant_id', $router->tenant_id)
            ->whereIn('slot', [1, 2])
            ->orderBy('slot')
            ->get()
            ->filter(fn ($s) => $this->isConfigured($s));

        if ($gateways->isEmpty()) {
            $transaction->update(['status' => 'failed']);

            return response()->json(['error' => 'This network has not finished setting up M-Pesa payments yet. Please try again later or contact support.'], 422);
        }

        $response = null;
        foreach ($gateways as $settings) {
            $response = $this->attemptStkPush($settings, $phone, $plan, $transaction, $router);

            if ($response !== null) {
                break;
            }
        }

        if ($response === null) {
            $transaction->update(['status' => 'failed']);

            return response()->json(['error' => 'Could not start the M-Pesa payment. Please try again.'], 422);
        }

        $transaction->update([
            'checkout_request_id' => $response['CheckoutRequestID'],
            'merchant_request_id' => $response['MerchantRequestID'] ?? null,
        ]);

        return response()->json(['transaction_id' => $transaction->id]);
    }

    /**
     * Checks that this gateway actually has somewhere to send the money, not just that the
     * toggle is on. It no longer needs its own Daraja app (consumer_key/secret/passkey) — a
     * tenant with no app of their own still gets pushed via RadiusPoint's shared gateway, see
     * MpesaService::for().
     */
    private function isConfigured(?MpesaSetting $settings): bool
    {
        if (! $settings || ! $settings->is_active) {
            return false;
        }

        return match ($settings->gateway_type) {
            'bank' => filled($settings->bank_paybill) && filled($settings->bank_account_number),
            default => filled($settings->shortcode),
        };
    }

    /**
     * Returns the Daraja response array on success, or null on any failure.
     */
    private function attemptStkPush(MpesaSetting $settings, string $phone, Plan $plan, Transaction $transaction, Router $router): ?array
    {
        try {
            $mpesa = MpesaService::for($settings);
            $response = $mpesa->stkPush(
                $phone,
                (float) $plan->price,
                'RadiusPoint-'.$transaction->id,
                $plan->name,
                route('api.mpesa.callback')
            );
        } catch (\Throwable $e) {
            Log::error('STK push threw an exception', ['tenant_id' => $router->tenant_id, 'slot' => $settings->slot, 'error' => $e->getMessage()]);

            return null;
        }

        if (! isset($response['CheckoutRequestID'])) {
            Log::warning('STK push failed to initiate', ['tenant_id' => $router->tenant_id, 'slot' => $settings->slot] + $response);

            return null;
        }

        return $response;
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
