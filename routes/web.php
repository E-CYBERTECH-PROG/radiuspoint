<?php

use App\Http\Controllers\ProfileController;
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

    // === ROUTER MANAGEMENT & ZTP WIZARD ===
    
    // This single line handles index, create, store, update, edit, and destroy
    Route::resource('routers', RouterController::class);

    // Custom ZTP Step 2: Copy Script & Check Status
    Route::get('/routers/{router}/provision', [RouterController::class, 'provision'])->name('routers.provision');
    Route::post('/routers/{router}/check-status', [RouterController::class, 'checkStatus'])->name('routers.check-status');

    // Custom ZTP Step 3 & 4: Select and Save Ports
    Route::get('/routers/{router}/ports', [RouterController::class, 'configurePorts'])->name('routers.ports');
    Route::post('/routers/{router}/ports', [RouterController::class, 'savePorts'])->name('routers.save-ports');

    // Operational health-check for already-configured routers (index/show live status + Test Connection button)
    Route::post('/routers/{router}/test-connection', [RouterController::class, 'testConnection'])->name('routers.test-connection');

    // === PLAN MANAGEMENT ===
    Route::resource('plans', PlanController::class)->except('show');

    // === PPPoE / HOTSPOT CUSTOMER MANAGEMENT ===
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
    Route::get('/sms/settings', [SmsSettingController::class, 'edit'])->name('sms-settings.edit');
    Route::put('/sms/settings', [SmsSettingController::class, 'update'])->name('sms-settings.update');

    // === M-PESA SETTINGS ===
    Route::get('/mpesa/settings', [MpesaSettingController::class, 'edit'])->name('mpesa-settings.edit');
    Route::put('/mpesa/settings', [MpesaSettingController::class, 'update'])->name('mpesa-settings.update');

    // === VOUCHERS ===
    Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::post('/vouchers/generate', [VoucherController::class, 'generate'])->name('vouchers.generate');
    Route::get('/vouchers/print', [VoucherController::class, 'print'])->name('vouchers.print');

    // === TEAM / OPERATORS ===
    Route::resource('team', TeamController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['team' => 'user']);
    Route::post('/team/{user}/reset-password', [TeamController::class, 'resetPassword'])->name('team.reset-password');

    // === TRANSACTIONS REPORT ===
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

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