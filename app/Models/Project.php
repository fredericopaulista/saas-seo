<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Project extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'domain',
        'country',
        'language',
        'gsc_property',
    ];

    public function searchConsoleToken()
    {
        return $this->hasOne(SearchConsoleToken::class);
    }

    public function performanceData()
    {
        return $this->hasMany(PerformanceData::class);
    }

    public function urlStatuses()
    {
        return $this->hasMany(UrlStatus::class);
    }

    public function seoScores()
    {
        return $this->hasMany(SeoScore::class);
    }

    public function insights()
    {
        return $this->hasMany(Insight::class);
    }
}
