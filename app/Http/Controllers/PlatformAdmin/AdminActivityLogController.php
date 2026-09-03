<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminActivityLogController extends Controller
{
    public const ACTIONS = [
        'approved',
        'rejected',
        'suspended',
        'reactivated',
        'updated',
        'imported',
        'exported_list',
        'exported_tenant_data',
    ];

    public function index(Request $request): View
    {
        $logs = AdminActivityLog::with(['admin', 'tenant'])
            ->when($request->filled('tenant_id'), fn ($q) => $q->where('tenant_id', $request->tenant_id))
            ->when($request->filled('admin_user_id'), fn ($q) => $q->where('admin_user_id', $request->admin_user_id))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))
            ->latest()
            ->paginate($this->perPage($request, 30))
            ->withQueryString();

        $tenants = Tenant::orderBy('company_name')->get(['id', 'company_name']);
        $admins = User::where('is_platform_admin', true)->orderBy('name')->get(['id', 'name']);
        $actions = self::ACTIONS;

        return view('platform-admin.activity-log.index', compact('logs', 'tenants', 'admins', 'actions'));
    }
}
