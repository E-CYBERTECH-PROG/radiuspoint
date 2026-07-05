<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class HotspotUser extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'phone_number',
        'mac_address',
        'current_plan_id',
        'current_router_id',
        'status',
        'expires_at'
    ];
}