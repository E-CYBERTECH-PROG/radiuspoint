<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Transaction extends Model
{
    use BelongsToTenant;

   protected $fillable = [
    'tenant_id',
    'mpesa_receipt',
    'checkout_request_id',
    'merchant_request_id',
    'plan_id',
    'hotspot_user_id',
    'amount',
    'customer_name',
    'phone_number',
    'package_name',
    'payment_method',
    'status',
];
}