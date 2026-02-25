<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'stripe_price_id',
        'features_json',
    ];

    protected function casts(): array
    {
        return [
            'features_json' => 'json',
        ];
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
