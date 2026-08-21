<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class HotspotUser extends Model
{
    use BelongsToTenant, HasFactory;

    /**
     * Single source of truth for valid statuses, used by validation rules and views.
     * Includes 'unused' for vouchers that have been generated but not yet redeemed.
     */
    public const STATUSES = ['unused', 'active', 'expired', 'offline'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class, 'current_router_id');
    }

    protected $fillable = [
        'tenant_id',
        'phone_number',
        'first_name',
        'last_name',
        'name',
        'email',
        'address',
        'mac_address',
        'current_plan_id',
        'current_router_id',
        'status',
        'is_voucher',
        'expires_at',
        'fup_throttled_at',
    ];

    protected $casts = [
        'is_voucher' => 'boolean',
        'expires_at' => 'datetime',
        'fup_throttled_at' => 'datetime',
    ];
}