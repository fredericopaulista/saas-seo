<?php

namespace App\Models;

use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

class Tenant extends SpatieTenant
{
    protected $table = 'tenants';

    protected $fillable = ['name', 'domain'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
