<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaptivePortal extends Model
{
    protected $fillable = [
        'tenant_id',
        'router_id',
        'template',
        'logo_url',
        'primary_color',
        'notice_title',
        'notice_body',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
