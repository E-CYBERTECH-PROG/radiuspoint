<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class CaptivePortalAnnouncement extends Model
{
    use BelongsToTenant;

    public const CATEGORIES = ['info', 'maintenance', 'promo', 'outage'];

    protected $fillable = [
        'tenant_id',
        'router_id',
        'category',
        'message',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function isActive(): bool
    {
        return ! $this->expires_at || $this->expires_at->isFuture();
    }
}
