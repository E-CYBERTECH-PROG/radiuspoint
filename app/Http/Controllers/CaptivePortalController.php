<?php

namespace App\Http\Controllers;

use App\Models\CaptivePortal;
use App\Models\CaptivePortalAnnouncement;
use App\Models\CaptivePortalVisit;
use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Services\RadiusSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Captive portal page shown to hotspot customers, hosted here rather than on the router.
 * RouterController writes a small redirect stub to the router's login.html that sends
 * customers here with RouterOS's $(link-login-only)/$(link-orig)/$(mac)/$(ip) as query params.
 */
class CaptivePortalController extends Controller
{
    public function show(Request $request, Router $router)
    {
        $tenant = Tenant::find($router->tenant_id);
        $portal = CaptivePortal::where('router_id', $router->id)->first();

        // Track the page view; feeds the Analytics conversion-rate report.
        CaptivePortalVisit::create(['tenant_id' => $router->tenant_id, 'router_id' => $router->id]);

        // Plans with no router restriction, or explicitly including this router, are sellable here.
        $plans = Plan::withoutGlobalScope('tenant')
            ->where('tenant_id', $router->tenant_id)
            ->where('type', 'hotspot')
            ->where(function ($query) use ($router) {
                $query->whereDoesntHave('routers')
                    ->orWhereHas('routers', fn ($q) => $q->where('routers.id', $router->id));
            })
            ->get();

        // Templates share the same self-hosted partials (no CDN assets); only hero/branding differs.
        $view = 'captive-portal.templates.'.($portal?->template ?? 'light-lumen');
        if (! view()->exists($view)) {
            $view = 'captive-portal.templates.light-lumen';
        }

        // Unauthenticated route, so filter tenant explicitly. Non-expired, and either global
        // (router_id null) or targeted at this specific router.
        $announcements = CaptivePortalAnnouncement::withoutGlobalScope('tenant')
            ->where('tenant_id', $router->tenant_id)
            ->where(fn ($q) => $q->whereNull('router_id')->orWhere('router_id', $router->id))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest()
            ->limit(3)
            ->get();

        // Reconnect a returning customer by device MAC (RouterOS supplies $mac on every portal
        // hit), scoped by tenant_id only so it follows them across all of this tenant's routers.
        // mac_address is backfilled from radacct shortly after their first connect.
        $autoReconnect = null;
        if ($mac = $request->query('mac')) {
            $existing = HotspotUser::withoutGlobalScope('tenant')
                ->where('tenant_id', $router->tenant_id)
                ->where('mac_address', $mac)
                ->where('status', 'active')
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->latest()
                ->first();

            if ($existing) {
                $autoReconnect = [
                    'username' => $existing->phone_number,
                    'password' => $this->radiusPassword($existing->phone_number),
                ];
            }
        }

        return view($view, [
            'tenant' => $tenant,
            'router' => $router,
            'portal' => $portal,
            'plans' => $plans,
            'announcements' => $announcements,
            'autoReconnect' => $autoReconnect,
            'linkLoginOnly' => $request->query('link-login-only'),
            'linkOrig' => $request->query('link-orig'),
            'mac' => $mac,
            'ip' => $request->query('ip'),
        ]);
    }

    /**
     * Phone-number self-service lookup for returning customers. Throttled at the route level
     * (6/min) since it's unauthenticated and could otherwise be used to enumerate numbers.
     */
    public function lookup(Request $request, Router $router)
    {
        $request->validate([
            'phone' => ['required', 'string', 'regex:/^(?:254|0)7\d{8}$/'],
        ]);

        $phone = $request->phone;
        if (str_starts_with($phone, '0')) {
            $phone = '254'.substr($phone, 1);
        }

        $hotspotUser = HotspotUser::withoutGlobalScope('tenant')
            ->where('tenant_id', $router->tenant_id)
            ->where('phone_number', $phone)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();

        if (! $hotspotUser) {
            return response()->json([
                'found' => false,
                'message' => 'No active plan found for this number. Buy a plan below to get connected.',
            ]);
        }

        return response()->json([
            'found' => true,
            'username' => $hotspotUser->phone_number,
            'password' => $this->radiusPassword($hotspotUser->phone_number),
        ]);
    }

    /**
     * Self-service lookup by pasted M-Pesa payment message, for customers who don't know or
     * remember the phone number they paid from. Same throttle tier as lookup() above.
     */
    public function lookupReceipt(Request $request, Router $router)
    {
        $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        // M-Pesa receipt codes are ~10-char alphanumeric with at least one letter and one digit,
        // which excludes phone numbers (all digits) and plain words (all letters).
        preg_match_all('/\b[A-Z0-9]{9,12}\b/', Str::upper($request->message), $matches);
        $code = collect($matches[0] ?? [])
            ->first(fn ($token) => preg_match('/[A-Z]/', $token) && preg_match('/[0-9]/', $token));

        if (! $code) {
            return response()->json([
                'found' => false,
                'message' => "Couldn't find a receipt code in that message. Paste the full M-Pesa confirmation text.",
            ], 422);
        }

        $transaction = Transaction::withoutGlobalScope('tenant')
            ->where('tenant_id', $router->tenant_id)
            ->where('status', 'success')
            ->where('mpesa_receipt', $code)
            ->latest()
            ->first();

        if (! $transaction) {
            return response()->json([
                'found' => false,
                'message' => 'No matching payment found for that receipt. Buy a plan below to get connected.',
            ]);
        }

        $hotspotUser = $transaction->hotspot_user_id
            ? HotspotUser::withoutGlobalScope('tenant')->find($transaction->hotspot_user_id)
            : HotspotUser::withoutGlobalScope('tenant')
                ->where('tenant_id', $router->tenant_id)
                ->where('phone_number', $transaction->phone_number)
                ->latest()
                ->first();

        if (! $hotspotUser || $hotspotUser->status !== 'active' || ($hotspotUser->expires_at && $hotspotUser->expires_at->isPast())) {
            return response()->json([
                'found' => false,
                'message' => 'Found that payment, but the plan has since expired. Buy a new one below.',
            ]);
        }

        return response()->json([
            'found' => true,
            'username' => $hotspotUser->phone_number,
            'password' => $this->radiusPassword($hotspotUser->phone_number),
        ]);
    }

    /**
     * Cleartext RADIUS password for a username — reused by lookup(), lookupReceipt(), and the
     * MAC auto-reconnect check in show().
     */
    private function radiusPassword(string $username): ?string
    {
        return DB::table('radcheck')
            ->where('username', $username)
            ->where('attribute', 'Cleartext-Password')
            ->value('value');
    }

    /**
     * Issues a throwaway RADIUS credential for Free Mode, rate-limited and restricted to
     * WhatsApp/Facebook via the router-side rules RouterController::provisionFreeMode() sets up.
     * Not a billing customer — no Plan/HotspotUser row, just a radcheck/radreply pair that
     * expires itself via Session-Timeout. Throttled at the route level (6/min).
     */
    public function freeMode(Request $request, Router $router)
    {
        $username = 'free_'.Str::lower(Str::random(10));
        $password = Str::random(12);

        RadiusSyncService::sync($username, $password, '96k/96k');
        RadiusSyncService::setGroup($username, $router->namingSlug().'_free');
        RadiusSyncService::setSessionTimeout($username, 1800);

        return response()->json([
            'username' => $username,
            'password' => $password,
            'buy_url' => route('captive.show', $router),
        ]);
    }
}
