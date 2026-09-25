<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class BalanceAdjustment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'hotspot_user_id',
        'pppoe_user_id',
        'kind',
        'amount',
        'previous_expires_at',
        'new_expires_at',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'previous_expires_at' => 'datetime',
        'new_expires_at' => 'datetime',
    ];

    public function hotspotUser()
    {
        return $this->belongsTo(HotspotUser::class);
    }

    public function pppoeUser()
    {
        return $this->belongsTo(PppoeUser::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
