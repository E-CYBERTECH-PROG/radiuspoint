<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\PppoeUser;
use App\Models\Router;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Global search modal (see resources/js/rp-search.js), across phone numbers, router names,
 * and transactions. Targeted LIKE lookups, not a full-text index.
 */
class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = (string) mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $tenantId = Auth::user()->tenant_id;

        $results = [
            'hotspot_users' => collect(),
            'pppoe_users' => collect(),
            'routers' => collect(),
            'transactions' => collect(),
        ];

        if ($q !== '') {
            $results['hotspot_users'] = HotspotUser::where('tenant_id', $tenantId)
                ->where('is_voucher', false)
                ->where('phone_number', 'like', "%{$q}%")
                ->limit(8)->get(['id', 'phone_number', 'status']);

            $results['pppoe_users'] = PppoeUser::where('tenant_id', $tenantId)
                ->where('username', 'like', "%{$q}%")
                ->limit(8)->get(['id', 'username', 'status']);

            $results['routers'] = Router::where('tenant_id', $tenantId)
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")->orWhere('ip_address', 'like', "%{$q}%");
                })
                ->limit(8)->get(['id', 'name', 'ip_address']);

            $results['transactions'] = Transaction::where('tenant_id', $tenantId)
                ->where(function ($query) use ($q) {
                    $query->where('customer_name', 'like', "%{$q}%")->orWhere('phone_number', 'like', "%{$q}%");
                })
                ->latest()->limit(8)->get(['id', 'customer_name', 'phone_number', 'amount']);
        }

        return response()->json([
            'hotspot_users' => $results['hotspot_users']->map(fn ($u) => [
                'label' => $u->phone_number, 'sub' => $u->status, 'url' => route('hotspot-users.show', $u),
            ]),
            'pppoe_users' => $results['pppoe_users']->map(fn ($u) => [
                'label' => $u->username, 'sub' => $u->status, 'url' => route('pppoe-users.show', $u),
            ]),
            'routers' => $results['routers']->map(fn ($r) => [
                'label' => $r->name, 'sub' => $r->ip_address, 'url' => route('routers.show', $r),
            ]),
            'transactions' => $results['transactions']->map(fn ($t) => [
                'label' => $t->customer_name, 'sub' => $t->phone_number, 'amount' => number_format($t->amount), 'url' => route('transactions.index'),
            ]),
        ]);
    }
}
