<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoScore extends Model
{
    protected $fillable = [
        'project_id',
        'score',
        'technical_score',
        'performance_score',
        'index_score',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
