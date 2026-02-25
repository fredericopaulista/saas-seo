<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UrlStatus extends Model
{
    protected $fillable = [
        'project_id',
        'url',
        'coverage_status',
        'index_status',
        'canonical_declared',
        'canonical_google',
        'mobile_usable',
        'last_crawled',
    ];

    protected function casts(): array
    {
        return [
            'mobile_usable' => 'boolean',
            'last_crawled' => 'datetime',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
