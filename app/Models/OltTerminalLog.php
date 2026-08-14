<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OltTerminalLog extends Model
{
    protected $fillable = [
        'user_id',
        'olt_id',
        'tenant_id',
        'command',
        'result',
        'success',
    ];

    protected $casts = [
        'success' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public static function record(User $user, Olt $olt, string $command, ?string $result, bool $success): self
    {
        return static::create([
            'user_id' => $user->id,
            'olt_id' => $olt->id,
            'tenant_id' => $olt->tenant_id,
            'command' => $command,
            'result' => $result,
            'success' => $success,
        ]);
    }
}
