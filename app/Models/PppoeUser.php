<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class PppoeUser extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'username',
        'name',
        'phone_number',
        'current_plan_id',
        'current_router_id',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
