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
        'testimonial_1_text',
        'testimonial_1_author',
        'testimonial_2_text',
        'testimonial_2_author',
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
