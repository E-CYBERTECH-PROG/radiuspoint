<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\HotspotUser;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\PppoeUser;
use App\Models\Router;
use App\Models\SmsMessage;
use App\Models\SmsSetting;
use App\Models\SmsTemplate;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class TenantDataExportController extends Controller
{
    public function exportList(Request $request): StreamedResponse
    {
        $search = $this->searchTerm($request);

        $tenants = Tenant::query()
            ->when($search, fn ($q) => $q->where('company_name', 'like', "%{$search}%"))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('tier'), fn ($q) => $q->where('subscription_tier', $request->tier))
            ->with('users')
            ->latest()
            ->get();

        AdminActivityLog::record($request->user(), null, 'exported_list', 'Exported '.$tenants->count().' tenants to CSV');

        return response()->streamDownload(function () use ($tenants) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Company Name', 'Status', 'Subscription Tier', 'Subscription Status', 'Subscription Expires At', 'Signup Date', 'Owner Name', 'Owner Email'], ',', '"', '');

            foreach ($tenants as $tenant) {
                $owner = $tenant->users->first();
                fputcsv($out, [
                    $tenant->company_name,
                    $tenant->status,
                    $tenant->subscription_tier,
                    $tenant->subscription_status,
                    optional($tenant->subscription_expires_at)->format('Y-m-d H:i'),
                    $tenant->created_at->format('Y-m-d H:i'),
                    $owner?->name,
                    $owner?->email,
                ], ',', '"', '');
            }

            fclose($out);
        }, 'tenants-'.now()->format('Y-m-d').'.csv');
    }

    public function exportTenantData(Request $request, Tenant $tenant)
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'tenant_export_');

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::OVERWRITE);

        $zip->addFromString('routers.csv', $this->csvFromModels(
            $this->tenantScoped(Router::class, $tenant)->get(),
            ['id', 'name', 'ip_address', 'api_username', 'status', 'last_seen', 'created_at']
        ));

        $zip->addFromString('pppoe_users.csv', $this->csvFromModels(
            $this->tenantScoped(PppoeUser::class, $tenant)->get(),
            ['id', 'username', 'name', 'phone_number', 'current_plan_id', 'current_router_id', 'status', 'expires_at']
        ));

        $zip->addFromString('hotspot_users.csv', $this->csvFromModels(
            $this->tenantScoped(HotspotUser::class, $tenant)->get(),
            ['id', 'phone_number', 'mac_address', 'current_plan_id', 'current_router_id', 'status', 'expires_at']
        ));

        $zip->addFromString('plans.csv', $this->csvFromModels(
            $this->tenantScoped(Plan::class, $tenant)->get(),
            ['id', 'name', 'type', 'price', 'duration_value', 'duration_unit', 'data_cap_mb', 'speed_limit']
        ));

        $zip->addFromString('transactions.csv', $this->csvFromModels(
            $this->tenantScoped(Transaction::class, $tenant)->get(),
            ['id', 'customer_name', 'phone_number', 'package_name', 'amount', 'payment_method', 'status', 'mpesa_receipt', 'created_at']
        ));

        $zip->addFromString('leads.csv', $this->csvFromModels(
            $this->tenantScoped(Lead::class, $tenant)->get(),
            ['id', 'name', 'phone', 'location', 'notes', 'status', 'created_at']
        ));

        $zip->addFromString('tickets.csv', $this->csvFromModels(
            $this->tenantScoped(Ticket::class, $tenant)->get(),
            ['id', 'username', 'phone', 'notes', 'status', 'created_at']
        ));

        $zip->addFromString('sms_messages.csv', $this->csvFromModels(
            $this->tenantScoped(SmsMessage::class, $tenant)->get(),
            ['id', 'phone_number', 'message', 'status', 'initiator', 'created_at']
        ));

        $zip->addFromString('sms_templates.csv', $this->csvFromModels(
            $this->tenantScoped(SmsTemplate::class, $tenant)->get(),
            ['id', 'name', 'body']
        ));

        $zip->addFromString('sms_settings.csv', $this->csvFromModels(
            $this->tenantScoped(SmsSetting::class, $tenant)->get(),
            ['id', 'provider', 'sender_id', 'username']
        ));

        $zip->close();

        AdminActivityLog::record(
            $request->user(),
            $tenant,
            'exported_tenant_data',
            'Exported full data export (routers, PPPoE/hotspot users, plans, transactions, etc.) — credentials excluded'
        );

        return response()->download($zipPath, "{$tenant->company_name}-export.zip")->deleteFileAfterSend(true);
    }

    private function csvFromModels($rows, array $columns): string
    {
        $buffer = fopen('php://temp', 'r+');
        fputcsv($buffer, $columns, ',', '"', '');

        foreach ($rows as $row) {
            fputcsv($buffer, array_map(fn ($column) => $row->{$column}, $columns), ',', '"', '');
        }

        rewind($buffer);
        $csv = stream_get_contents($buffer);
        fclose($buffer);

        return $csv;
    }

    private function tenantScoped(string $modelClass, Tenant $tenant)
    {
        return $modelClass::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id);
    }
}
