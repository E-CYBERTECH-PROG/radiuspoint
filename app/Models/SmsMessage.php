<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class SmsMessage extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'phone_number',
        'message',
        'status',
        'initiator',
        'sms_template_id',
    ];
}
