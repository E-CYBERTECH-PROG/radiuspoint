<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class HotspotUser extends Model
{
    use BelongsToTenant;

    /**
     * Single source of truth for every place that validates or lists a status — previously
     * hand-duplicated as "active,expired,offline" in 4 separate places (2 controller validation
     * rules, 2 Blade views) and every one of them silently missed "unused" (a real DB status,
     * set on voucher generation). Editing an unredeemed voucher — a reachable URL, since vouchers
     * are plain HotspotUser rows — hit a <select> with no matching <option>, so the browser
     * defaulted to "active" and submitting the form (even unchanged) silently activated/RADIUS-
     * synced a voucher nobody had actually redeemed. Confirmed live via a real code audit, not
     * theoretical.
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
        'mac_address',
        'current_plan_id',
        'current_router_id',
        'status',
        'expires_at',
        'fup_throttled_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'fup_throttled_at' => 'datetime',
    ];
}