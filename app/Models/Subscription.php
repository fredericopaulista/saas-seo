<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Subscription extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'stripe_id',
        'plan_id',
        'status',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'ends_at' => 'datetime',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
