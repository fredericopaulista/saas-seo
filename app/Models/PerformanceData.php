<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceData extends Model
{
    protected $fillable = [
        'project_id',
        'date',
        'page',
        'query',
        'clicks',
        'impressions',
        'ctr',
        'position',
        'device',
        'country',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
