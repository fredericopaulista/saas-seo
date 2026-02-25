<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class SystemMetric extends Model
{
    protected $fillable = [
        'metric_key',
        'value',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'value' => 'decimal:2',
        ];
    }
}
