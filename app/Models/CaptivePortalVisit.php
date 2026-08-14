<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaptivePortalVisit extends Model
{
    public $timestamps = false;

    protected $fillable = ['tenant_id', 'router_id', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
