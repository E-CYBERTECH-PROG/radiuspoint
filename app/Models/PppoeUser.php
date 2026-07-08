<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class PppoeUser extends Model
{
    use BelongsToTenant;

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
        'username',
        'name',
        'phone_number',
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
