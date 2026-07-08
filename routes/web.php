<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PppoeUserController;
use App\Http\Controllers\HotspotUserController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\SmsMessageController;
use App\Http\Controllers\SmsTemplateController;
use App\Http\Controllers\SmsSettingController;
use App\Http\Controllers\MpesaSettingController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\PaymentPortalController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PlatformAdmin\TenantController;
use App\Http\Controllers\PlatformAdmin\TenantDataExportController;
use App\Http\Controllers\PlatformAdmin\TenantImportController;
use App\Http\Controllers\PlatformAdmin\AdminActivityLogController;
use App\Http\Controllers\NasProvisioningController;
use App\Http\Controllers\CaptivePortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Shown to a logged-in ISP user whose tenant hasn't been approved by a platform admin yet
Route::middleware('auth')->get('/pending-approval', function () {
    return view('tenant.pending');
})->name('tenant.pending');

// Public, unauthenticated customer self-service M-Pesa payment portal (no login — reached via a router's public_token)
Route::get('/portal/{router:public_token}', [PaymentPortalController::class, 'show'])->name('portal.show');
Route::post('/portal/{router:public_token}/pay', [PaymentPortalController::class, 'pay'])->name('portal.pay');
Route::get('/portal/{router:public_token}/status/{transaction}', [PaymentPortalController::class, 'status'])->name('portal.status');

// Public, unauthenticated ZTP bootstrap fetch — a router's own script calls this to pull
// its real config (see RouterController::provision() / Router::buildProvisioningScript()).
Route::get('/nas/startup/{router:public_token}', [NasProvisioningController::class, 'startup'])->name('nas.startup');

// Public, unauthenticated hotspot captive portal — reached via the tiny local login.html
// redirect stub RouterOS serves (see RouterController::provisionHotspot()'s walled-garden +
// file-write step). Hosted here rather than on the router's own limited local storage.
Route::get('/captive/{router:public_token}', [CaptivePortalController::class, 'show'])->name('captive.show');
Route::post('/captive/{router:public_token}/lookup', [CaptivePortalController::class, 'lookup'])
    ->middleware('throttle:6,1')
    ->name('captive.lookup');

// Platform-team only: manage ISP tenant accounts
Route::middleware(['auth', 'platform.admin'])->prefix('platform-admin')->name('platform-admin.')->group(function () {
    Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/import', [TenantImportController::class, 'importForm'])->name('tenants.import-form');
    Route::get('/tenants/import/template', [TenantImportController::class, 'template'])->name('tenants.import-template');
    Route::post('/tenants/import', [TenantImportController::class, 'import'])->name('tenants.import');
    Route::get('/tenants/export', [TenantDataExportController::class, 'exportList'])->name('tenants.export');
    Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
    Route::get('/tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
    Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
    Route::post('/tenants/{tenant}/approve', [TenantController::class, 'approve'])->name('tenants.approve');
    Route::post('/tenants/{tenant}/reject', [TenantController::class, 'reject'])->name('tenants.reject');
    Route::post('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
    Route::post('/tenants/{tenant}/reactivate', [TenantController::class, 'reactivate'])->name('tenants.reactivate');
    Route::get('/tenants/{tenant}/export-data', [TenantDataExportController::class, 'exportTenantData'])->name('tenants.export-data');
    Route::get('/activity-log', [AdminActivityLogController::class, 'index'])->name('activity-log.index');
});

// All routes wrapped in auth to ensure only logged-in, approved ISPs can access them
Route::middleware(['auth', 'verified', 'tenant.approved'])->group(function () {

    // The Command Center Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/live-count', [DashboardController::class, 'liveCount'])->name('dashboard.live-count');
    Route::get('/dashboard/live-snapshot', [DashboardController::class, 'liveSnapshot'])->name('dashboard.live-snapshot');

    // === NOTIFICATIONS ===
    Route::get('/settings/notifications', [NotificationPreferenceController::class, 'edit'])->name('settings.notifications.edit');
    Route::post('/settings/notifications', [NotificationPreferenceController::class, 'update'])->name('settings.notifications.update');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');

    // === ROUTER MANAGEMENT & ZTP WIZARD ===

    // Read-only for everyone, including Sales Agent — see the restrict.sales-agent group below
    // for everything else (per-router detail/settings, Live Monitor, control actions, terminal).
    Route::resource('routers', RouterController::class)->only(['index']);
    Route::get('/routers-noc', [RouterController::class, 'noc'])->name('routers.noc');

    Route::middleware('restrict.sales-agent')->group(function () {
        // This single line handles create, store, show, update, and destroy (index is registered above)
        Route::resource('routers', RouterController::class)->except(['index']);

        // Custom ZTP Step 2: Copy Script & Check Status
        Route::get('/routers/{router}/provision', [RouterController::class, 'provision'])->name('routers.provision');
        Route::post('/routers/{router}/check-status', [RouterController::class, 'checkStatus'])->name('routers.check-status');

        // Custom ZTP Step 3 & 4: Select and Save Ports
        Route::get('/routers/{router}/ports', [RouterController::class, 'configurePorts'])->name('routers.ports');
        Route::post('/routers/{router}/ports', [RouterController::class, 'savePorts'])->name('routers.save-ports');

        // Operational health-check for already-configured routers (index/show live status + Test Connection button)
        Route::post('/routers/{router}/test-connection', [RouterController::class, 'testConnection'])->name('routers.test-connection');

        // === LIVE MONITOR (BillNasi-style per-router dashboard: logs, active sessions, interfaces) ===
        Route::get('/routers/{router}/monitor', [RouterController::class, 'monitor'])->name('routers.monitor');
        Route::get('/routers/{router}/monitor/logs', [RouterController::class, 'monitorLogs'])->name('routers.monitor.logs');
        Route::get('/routers/{router}/monitor/interfaces', [RouterController::class, 'monitorInterfaces'])->name('routers.monitor.interfaces');
        Route::get('/routers/{router}/monitor/hotspot-active', [RouterController::class, 'monitorHotspotActive'])->name('routers.monitor.hotspot-active');
        Route::get('/routers/{router}/monitor/pppoe-active', [RouterController::class, 'monitorPppoeActive'])->name('routers.monitor.pppoe-active');
        Route::get('/routers/{router}/monitor/addresses', [RouterController::class, 'monitorAddresses'])->name('routers.monitor.addresses');
        Route::get('/routers/{router}/monitor/neighbors', [RouterController::class, 'monitorNeighbors'])->name('routers.monitor.neighbors');
        Route::get('/routers/{router}/monitor/interface-list', [RouterController::class, 'monitorInterfaceList'])->name('routers.monitor.interface-list');
        Route::get('/routers/{router}/monitor/traffic', [RouterController::class, 'monitorTraffic'])->name('routers.monitor.traffic');
        Route::get('/routers/{router}/monitor/dhcp-leases', [RouterController::class, 'monitorDhcpLeases'])->name('routers.monitor.dhcp-leases');
        Route::get('/routers/{router}/monitor/wireless-clients', [RouterController::class, 'monitorWirelessClients'])->name('routers.monitor.wireless-clients');
        Route::get('/routers/{router}/monitor/health', [RouterController::class, 'monitorHealth'])->name('routers.monitor.health');
        Route::get('/routers/{router}/monitor/queues', [RouterController::class, 'monitorQueues'])->name('routers.monitor.queues');
        Route::get('/routers/{router}/monitor/firewall-rules', [RouterController::class, 'monitorFirewallRules'])->name('routers.monitor.firewall-rules');
        Route::get('/routers/{router}/monitor/torch', [RouterController::class, 'monitorTorch'])->name('routers.monitor.torch');
        Route::get('/routers/{router}/monitor/resource', [RouterController::class, 'monitorResource'])->name('routers.monitor.resource');

        // === CAPTIVE PORTAL SETTINGS ===
        Route::post('/routers/{router}/captive-portal', [RouterController::class, 'updateCaptivePortal'])->name('routers.captive-portal.update');

        // === REMOTE CONTROL ACTIONS (Live Monitor's action buttons) ===
        Route::post('/routers/{router}/actions/reboot', [RouterController::class, 'reboot'])->name('routers.actions.reboot');
        Route::post('/routers/{router}/actions/toggle-interface', [RouterController::class, 'toggleInterface'])->name('routers.actions.toggle-interface');
        Route::post('/routers/{router}/actions/disconnect-hotspot', [RouterController::class, 'disconnectHotspotUser'])->name('routers.actions.disconnect-hotspot');
        Route::post('/routers/{router}/actions/disconnect-pppoe', [RouterController::class, 'disconnectPppoeUser'])->name('routers.actions.disconnect-pppoe');
        Route::post('/routers/{router}/actions/block-hotspot', [RouterController::class, 'blockHotspotUser'])->name('routers.actions.block-hotspot');

        // === LIVE TERMINAL/CONSOLE ===
        Route::get('/routers/{router}/terminal/history', [RouterController::class, 'terminalHistory'])->name('routers.terminal.history');
        Route::post('/routers/{router}/terminal/execute', [RouterController::class, 'terminalExecute'])->name('routers.terminal.execute');
    });

    // === PLAN MANAGEMENT ===
    Route::resource('plans', PlanController::class)->except('show');
    Route::get('/plans/{plan}/sync-status', [PlanController::class, 'syncStatus'])->name('plans.sync-status');

    // === PPPoE / HOTSPOT CUSTOMER MANAGEMENT ===
    // live-status routes must be registered before the resource routes below — otherwise
    // /pppoe-users/live-status would be swallowed by the resource's /pppoe-users/{pppoe_user}
    // show route (first-registered-wins route matching).
    Route::get('/pppoe-users/live-status', [PppoeUserController::class, 'liveStatus'])->name('pppoe-users.live-status');
    Route::get('/hotspot-users/live-status', [HotspotUserController::class, 'liveStatus'])->name('hotspot-users.live-status');
    Route::resource('pppoe-users', PppoeUserController::class);
    Route::resource('hotspot-users', HotspotUserController::class);

    // === LEADS (CRM) ===
    Route::resource('leads', LeadController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::patch('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.update-status');
    Route::post('/leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');

    // === SUPPORT TICKETS ===
    Route::resource('tickets', TicketController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.update-status');

    // === SMS OUTBOX & TEMPLATES ===
    Route::resource('sms', SmsMessageController::class)->only(['index', 'store', 'destroy']);
    Route::resource('sms-templates', SmsTemplateController::class)->only(['store', 'update', 'destroy']);

    // Gateway credentials (SMS provider, M-Pesa Daraja keys) — tenant-wide config, not
    // customer-facing work, so this stays out of Sales Agent's reach even though the SMS
    // outbox itself (sending messages) doesn't.
    Route::middleware('restrict.sales-agent')->group(function () {
        Route::get('/sms/settings', [SmsSettingController::class, 'edit'])->name('sms-settings.edit');
        Route::put('/sms/settings', [SmsSettingController::class, 'update'])->name('sms-settings.update');
        Route::get('/mpesa/settings', [MpesaSettingController::class, 'edit'])->name('mpesa-settings.edit');
        Route::put('/mpesa/settings', [MpesaSettingController::class, 'update'])->name('mpesa-settings.update');
    });

    // === VOUCHERS ===
    Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::post('/vouchers/generate', [VoucherController::class, 'generate'])->name('vouchers.generate');
    Route::get('/vouchers/print', [VoucherController::class, 'print'])->name('vouchers.print');

    // === TEAM / OPERATORS ===
    Route::resource('team', TeamController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['team' => 'user']);
    Route::post('/team/{user}/reset-password', [TeamController::class, 'resetPassword'])->name('team.reset-password');

    // === TRANSACTIONS REPORT ===
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/live-status', [TransactionController::class, 'liveStatus'])->name('transactions.live-status');

    // === REPORTS ===
    Route::get('/reports/pppoe-balances', [ReportController::class, 'pppoeBalances'])->name('reports.pppoe-balances');
    Route::get('/reports/fixed-sales', [ReportController::class, 'fixedSales'])->name('reports.fixed-sales');
    Route::get('/reports/hotspot-sales', [ReportController::class, 'hotspotSales'])->name('reports.hotspot-sales');
    Route::get('/reports/access-log', [ReportController::class, 'accessLog'])->name('reports.access-log');
});

// Default Laravel Breeze Profile Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';