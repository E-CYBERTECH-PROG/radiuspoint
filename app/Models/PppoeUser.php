<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class PppoeUser extends Model
{
    use BelongsToTenant, HasFactory;

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class, 'current_router_id');
    }

    /**
     * The RADIUS username — always the username column for PPPoE (no receipt-based scheme
     * here). Exists so callers shared with HotspotUser (whose RADIUS username isn't always
     * phone_number — see HotspotUser::radiusUsername()) can treat both the same way.
     */
    public function radiusUsername(): string
    {
        return $this->username;
    }

    protected $fillable = [
        'tenant_id',
        'username',
        'first_name',
        'last_name',
        'name',
        'phone_number',
        'email',
        'address',
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
