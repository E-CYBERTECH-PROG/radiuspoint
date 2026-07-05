<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class MpesaSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'gateway_type',
        'shortcode',
        'consumer_key',
        'consumer_secret',
        'passkey',
        'environment',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
