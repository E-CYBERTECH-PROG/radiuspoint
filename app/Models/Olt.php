<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Olt extends Model
{
    use BelongsToTenant;

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function terminalLogs(): HasMany
    {
        return $this->hasMany(OltTerminalLog::class);
    }

    protected $fillable = [
        'tenant_id', 'name', 'brand', 'ip_address', 'ssh_port',
        'username', 'password', 'status', 'last_seen', 'pon_ports', 'notes',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
    ];
}
