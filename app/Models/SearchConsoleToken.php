<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchConsoleToken extends Model
{
    protected $fillable = [
        'project_id',
        'access_token',
        'refresh_token',
        'expires_in',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
