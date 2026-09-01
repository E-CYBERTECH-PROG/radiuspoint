<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class MpesaSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'slot',
        'gateway_type',
        'shortcode',
        'account_number',
        'bank_paybill',
        'bank_account_number',
        'consumer_key',
        'consumer_secret',
        'passkey',
        'environment',
        'is_active',
        'consecutive_failures',
        'last_checked_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
    ];
}
