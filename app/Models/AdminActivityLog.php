<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActivityLog extends Model
{
    protected $fillable = [
        'admin_user_id',
        'tenant_id',
        'action',
        'details',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function record(User $admin, ?Tenant $tenant, string $action, ?string $details = null): self
    {
        return static::create([
            'admin_user_id' => $admin->id,
            'tenant_id' => $tenant?->id,
            'action' => $action,
            'details' => $details,
        ]);
    }
}
