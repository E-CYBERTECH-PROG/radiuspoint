<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouterTerminalLog extends Model
{
    protected $fillable = [
        'user_id',
        'router_id',
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

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public static function record(User $user, Router $router, string $command, ?string $result, bool $success): self
    {
        return static::create([
            'user_id' => $user->id,
            'router_id' => $router->id,
            'tenant_id' => $router->tenant_id,
            'command' => $command,
            'result' => $result,
            'success' => $success,
        ]);
    }
}
