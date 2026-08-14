<?php

namespace App\Http\Controllers;

use App\Models\CaptivePortal;
use App\Models\CaptivePortalAnnouncement;
use App\Models\CaptivePortalVisit;
use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Tenant;
use App\Services\RadiusSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The actual captive portal experience a hotspot customer sees, hosted here rather than
 * uploaded as static files to each router's local storage — see the tiny redirect stub
 * RouterController writes to a router's own hotspot/login.html (routers.php provisioning),
 * which sends the customer here carrying RouterOS's own $(link-login-only)/$(link-orig)/
 * $(mac)/$(ip) template variables as query params.
 */
class CaptivePortalController extends Controller
{
    public function show(Request $request, Router $router)
    {
        $tenant = Tenant::find($router->tenant_id);
        $portal = CaptivePortal::where('router_id', $router->id)->first();

        // One row per page load — the only visibility this session's earlier work gave into
        // portal performance was after-the-fact (successful Transactions), with no way to tell
        // "50 people bought" from "50 people bought out of 50 who ever saw the page" vs "out of
        // 5000". Feeds the Reports > Analytics conversion-rate section.
        CaptivePortalVisit::create(['tenant_id' => $router->tenant_id, 'router_id' => $router->id]);

        // Same "no router restriction, or explicitly includes this router" rule PlanReconcile
        // uses — a plan restricted to a different router shouldn't be sellable here.
        $plans = Plan::withoutGlobalScope('tenant')
            ->where('tenant_id', $router->tenant_id)
            ->where('type', 'hotspot')
            ->where(function ($query) use ($router) {
                $query->whereDoesntHave('routers')
                    ->orWhereHas('routers', fn ($q) => $q->where('routers.id', $router->id));
            })
            ->get();

        // All three templates share the same self-hosted partials (see the template files'
        // own comment for why they can't use CDN assets) — only the hero/branding differs.
        $view = 'captive-portal.templates.'.($portal?->template ?? 'default');
        if (! view()->exists($view)) {
            $view = 'captive-portal.templates.default';
        }

        // Unauthenticated route, so no tenant global scope applies anyway — explicit tenant_id
        // filter matches the same pattern $plans above already uses. Non-expired, and either
        // global (router_id null) or targeted at this specific router.
        $announcements = CaptivePortalAnnouncement::withoutGlobalScope('tenant')
            ->where('tenant_id', $router->tenant_id)
            ->where(fn ($q) => $q->whereNull('router_id')->orWhere('router_id', $router->id))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest()
            ->limit(3)
            ->get();

        return view($view, [
            'tenant' => $tenant,
            'router' => $router,
            'portal' => $portal,
            'plans' => $plans,
            'announcements' => $announcements,
            'linkLoginOnly' => $request->query('link-login-only'),
            'linkOrig' => $request->query('link-orig'),
            'mac' => $request->query('mac'),
            'ip' => $request->query('ip'),
        ]);
    }

    /**
     * Phone-number self-service lookup — throttled at the route level (6/min) since this is an
     * unauthenticated endpoint and a phone number is enough to find whether someone has a live
     * plan, making it a real enumeration target without a tight limit.
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

        $password = DB::table('radcheck')
            ->where('username', $hotspotUser->phone_number)
            ->where('attribute', 'Cleartext-Password')
            ->value('value');

        return response()->json([
            'found' => true,
            'username' => $hotspotUser->phone_number,
            'password' => $password,
        ]);
    }

    /**
     * Free Mode: a throwaway RADIUS credential, throttled to a low rate cap and (best-effort)
     * restricted to WhatsApp/Facebook domains via the router-side rules RouterController's
     * provisionFreeMode() sets up. Not a billing customer — no Plan, no HotspotUser row, just a
     * short-lived radcheck/radreply pair that expires itself via Session-Timeout, no cron needed.
     * Throttled at the route level (6/min) to stop someone scripting endless free sessions.
     */
    public function freeMode(Request $request, Router $router)
    {
        $username = 'free_'.Str::lower(Str::random(10));
        $password = Str::random(12);

        RadiusSyncService::sync($username, $password, '96k/96k');
        RadiusSyncService::setGroup($username, 'radiuspoint_free');
        RadiusSyncService::setSessionTimeout($username, 1800);

        return response()->json([
            'username' => $username,
            'password' => $password,
            'buy_url' => route('captive.show', $router),
        ]);
    }
}
