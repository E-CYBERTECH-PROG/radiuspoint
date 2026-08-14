<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\TenantInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        // withoutGlobalScope throughout: TenantInvoice uses BelongsToTenant, which would
        // otherwise silently scope every query here to the platform admin's own internal
        // tenant row instead of showing invoices across all tenants.
        $invoices = TenantInvoice::withoutGlobalScope('tenant')
            ->with('tenant')
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'overdue') {
                    $q->where('status', 'pending')->where('due_at', '<', now());
                } else {
                    $q->where('status', $request->status);
                }
            })
            ->latest('period_start')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $totals = [
            'outstanding' => TenantInvoice::withoutGlobalScope('tenant')->where('status', 'pending')->sum('amount_due'),
            'overdue_count' => TenantInvoice::withoutGlobalScope('tenant')->where('status', 'pending')->where('due_at', '<', now())->count(),
            'collected_this_month' => TenantInvoice::withoutGlobalScope('tenant')->where('status', 'paid')
                ->whereMonth('paid_at', now()->month)->whereYear('paid_at', now()->year)->sum('amount_due'),
        ];

        return view('platform-admin.invoices.index', compact('invoices', 'totals'));
    }

    public function markPaid(Request $request, int $invoice): RedirectResponse
    {
        $invoice = TenantInvoice::withoutGlobalScope('tenant')->with('tenant')->findOrFail($invoice);

        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
            'marked_paid_by' => $request->user()->id,
        ]);

        AdminActivityLog::record(
            $request->user(),
            $invoice->tenant,
            'marked_invoice_paid',
            "Marked {$invoice->period_start->format('F Y')} commission invoice paid (KES ".number_format($invoice->amount_due, 2).')'
        );

        return back()->with('success', "{$invoice->tenant->company_name}'s {$invoice->period_start->format('F Y')} invoice marked as paid.");
    }
}
