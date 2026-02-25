<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $currentTenant = SpatieTenant::current();
            if ($currentTenant) {
                $builder->where('tenant_id', $currentTenant->id);
            }
        });

        static::creating(function ($model) {
            $currentTenant = SpatieTenant::current();
            if ($currentTenant && ! $model->tenant_id) {
                $model->tenant_id = $currentTenant->id;
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
