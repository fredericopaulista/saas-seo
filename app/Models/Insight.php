<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Insight extends Model
{
    protected $fillable = [
        'project_id',
        'type',
        'severity',
        'title',
        'description',
        'metadata',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'json',
            'resolved_at' => 'datetime',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
