<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantImported;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantImportController extends Controller
{
    public function importForm(): View
    {
        return view('platform-admin.tenants.import');
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['company_name', 'subdomain', 'owner_name', 'owner_email', 'subscription_tier'], ',', '"', '');
            fputcsv($out, ['Acme Wireless', 'acme', 'Jane Doe', 'jane@acmewireless.com', 'free'], ',', '"', '');
            fclose($out);
        }, 'tenant-import-template.csv');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle, 0, ',', '"', '');

        $created = [];
        $failed = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $rowNumber++;
            $data = array_combine($header, $row);

            try {
                $companyName = trim($data['company_name'] ?? '');
                $ownerEmail = trim($data['owner_email'] ?? '');
                $ownerName = trim($data['owner_name'] ?? '');
                $subdomain = trim($data['subdomain'] ?? '') ?: null;
                $subscriptionTier = trim($data['subscription_tier'] ?? '') ?: 'free';

                if ($companyName === '') {
                    throw new \RuntimeException('company_name is required');
                }
                if ($ownerEmail === '' || ! filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('owner_email is missing or invalid');
                }
                if ($ownerName === '') {
                    throw new \RuntimeException('owner_name is required');
                }
                if (! in_array($subscriptionTier, ['free', 'starter', 'pro'], true)) {
                    throw new \RuntimeException("subscription_tier '{$subscriptionTier}' is not one of free, starter, pro");
                }
                if (Tenant::where('company_name', $companyName)->exists()) {
                    throw new \RuntimeException("company_name '{$companyName}' already exists");
                }
                if ($subdomain && Tenant::where('subdomain', $subdomain)->exists()) {
                    throw new \RuntimeException("subdomain '{$subdomain}' already exists");
                }
                if (User::where('email', $ownerEmail)->exists()) {
                    throw new \RuntimeException("owner_email '{$ownerEmail}' already exists");
                }

                $temporaryPassword = Str::password(16);

                $user = DB::transaction(function () use ($companyName, $subdomain, $subscriptionTier, $ownerName, $ownerEmail, $temporaryPassword) {
                    $tenant = Tenant::create([
                        'company_name' => $companyName,
                        'subdomain' => $subdomain,
                        'status' => 'pending',
                        'subscription_tier' => $subscriptionTier,
                        'subscription_status' => 'trial',
                    ]);

                    return User::create([
                        'tenant_id' => $tenant->id,
                        'name' => $ownerName,
                        'email' => $ownerEmail,
                        'password' => $temporaryPassword,
                        'role' => 'SuperAdmin',
                    ]);
                });

                $user->notify(new TenantImported($temporaryPassword));

                $created[] = ['row' => $rowNumber, 'company_name' => $companyName, 'owner_email' => $ownerEmail];
            } catch (\Throwable $e) {
                $failed[] = ['row' => $rowNumber, 'reason' => $e->getMessage()];
            }
        }

        fclose($handle);

        AdminActivityLog::record(
            $request->user(),
            null,
            'imported',
            count($created).' created, '.count($failed).' failed'
        );

        return redirect()->route('platform-admin.tenants.import-form')
            ->with('importResults', ['created' => $created, 'failed' => $failed]);
    }
}
