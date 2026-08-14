<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

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
     * Trial has run out. Display only — access is actually gated by
     * isBillingLocked(), not trial status alone.
     */
    public function isTrialExpired(): bool
    {
        return $this->subscription_status === 'trial' && $this->isSubscriptionExpired();
    }

    /**
     * True once a commission invoice has gone unpaid past its grace period.
     * Blocks dashboard access — see EnsureTenantSubscribed.
     */
    public function isBillingLocked(): bool
    {
        // Bypass the tenant scope so this checks all invoices regardless of who triggered it
        return $this->invoices()->withoutGlobalScope('tenant')
            ->where('status', 'pending')->where('due_at', '<', now())->exists();
    }
}