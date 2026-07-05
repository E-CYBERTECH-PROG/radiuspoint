<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Lead extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'phone',
        'location',
        'notes',
        'status',
        'converted_to_pppoe_user_id',
        'converted_to_hotspot_user_id',
    ];
}
