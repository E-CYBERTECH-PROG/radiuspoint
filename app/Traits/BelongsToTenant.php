<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function booted()
    {
        // Whenever a model is queried, automatically filter by the logged-in ISP's ID
        if (auth()->check()) {
            static::addGlobalScope('tenant', function (Builder $builder) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            });
        }
    }

    // Automatically assign the ISP's ID when they create a new router or plan
    protected static function bootBelongsToTenant()
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
