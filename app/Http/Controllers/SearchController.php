<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\PppoeUser;
use App\Models\Router;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Search box across phone numbers, router names, and transactions. Targeted LIKE lookups,
 * not a full-text index.
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
                ->where('phone_number', 'like', "%{$q}%")
                ->limit(8)->get();

            $results['pppoe_users'] = PppoeUser::where('tenant_id', $tenantId)
                ->where('username', 'like', "%{$q}%")
                ->limit(8)->get();

            $results['routers'] = Router::where('tenant_id', $tenantId)
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")->orWhere('ip_address', 'like', "%{$q}%");
                })
                ->limit(8)->get();

            $results['transactions'] = Transaction::where('tenant_id', $tenantId)
                ->where(function ($query) use ($q) {
                    $query->where('customer_name', 'like', "%{$q}%")->orWhere('phone_number', 'like', "%{$q}%");
                })
                ->latest()->limit(8)->get();
        }

        return view('search.index', ['q' => $q, 'results' => $results]);
    }
}
