<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    // A model class boots once per process. Registering the scope only when someone was
    // already logged in at that moment meant a model first touched before auth resolved stayed
    // unscoped — across every tenant — for the rest of the request (or test). The scope is now
    // always registered and checks auth at query time instead. bootBelongsToTenant() (rather
    // than booted()) also can't be silently overridden by a model defining its own booted().
    protected static function bootBelongsToTenant()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where($builder->qualifyColumn('tenant_id'), auth()->user()->tenant_id);
            }
        });

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
