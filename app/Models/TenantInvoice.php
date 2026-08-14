<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A monthly commission bill: 3% of a tenant's total successful-transaction revenue for one
 * calendar month, issued on the 1st of the following month (see GenerateTenantInvoices).
 * Paid either via self-service M-Pesa STK push (TenantBillingController::pay(), verified
 * automatically by PlatformBillingController::handleCallback()) or manually marked paid by a
 * platform admin as a fallback for any other payment channel.
 */
class TenantInvoice extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'period_start',
        'period_end',
        'revenue_total',
        'commission_rate',
        'amount_due',
        'status',
        'due_at',
        'paid_at',
        'marked_paid_by',
        'notes',
        'checkout_request_id',
        'merchant_request_id',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'revenue_total' => 'decimal:2',
        'amount_due' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function markedPaidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_paid_by');
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_at->isPast();
    }
}
