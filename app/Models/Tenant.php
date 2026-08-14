<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    // Tell Laravel we are allowed to save these fields
    protected $fillable = [
        'company_name',
        'support_phone',
        'location',
        'timezone',
        'currency_symbol',
        'subdomain',
        'status',
        'subscription_tier',
        'subscription_status',
        'subscription_expires_at',
        'admin_notes',
    ];

    protected $casts = [
        'subscription_expires_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(TenantInvoice::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'active';
    }

    public function isSubscriptionExpired(): bool
    {
        return $this->subscription_expires_at && $this->subscription_expires_at->isPast();
    }

    /**
     * Trial has run out and they haven't converted — display only; access is actually gated
     * by unpaid commission invoices past their grace period (see isBillingLocked()), not by
     * trial status alone, since billing only starts once a full calendar month has closed.
     */
    public function isTrialExpired(): bool
    {
        return $this->subscription_status === 'trial' && $this->isSubscriptionExpired();
    }

    /**
     * True once a monthly commission invoice has gone unpaid past its grace period — the
     * actual condition that blocks dashboard access (see EnsureTenantSubscribed).
     */
    public function isBillingLocked(): bool
    {
        // withoutGlobalScope: this must see every invoice belonging to *this* tenant regardless
        // of which user (platform admin, or the tenant itself) triggered the check.
        return $this->invoices()->withoutGlobalScope('tenant')
            ->where('status', 'pending')->where('due_at', '<', now())->exists();
    }
}