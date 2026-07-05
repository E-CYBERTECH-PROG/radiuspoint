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

    public function isApproved(): bool
    {
        return $this->status === 'active';
    }

    public function isSubscriptionExpired(): bool
    {
        return $this->subscription_expires_at && $this->subscription_expires_at->isPast();
    }
}