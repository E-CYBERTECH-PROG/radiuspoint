<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function booted()
    {
        if (auth()->check()) {
            static::addGlobalScope('tenant', function (Builder $builder) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            });
        }
    }

    protected static function bootBelongsToTenant()
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
