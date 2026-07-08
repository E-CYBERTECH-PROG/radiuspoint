<?php

namespace App\Http\Controllers;

use App\Models\CaptivePortal;
use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $template = $portal?->template ?? 'minimal';

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

        $view = "captive-portal.templates.{$template}";
        if (! view()->exists($view)) {
            $view = 'captive-portal.templates.minimal';
        }

        return view($view, [
            'tenant' => $tenant,
            'router' => $router,
            'portal' => $portal,
            'plans' => $plans,
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
}
