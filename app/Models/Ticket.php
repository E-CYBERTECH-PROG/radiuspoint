<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Ticket extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'username',
        'phone',
        'notes',
        'status',
    ];
}
