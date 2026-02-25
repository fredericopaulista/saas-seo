<?php

namespace App\Services\Admin;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Project;
use App\Models\Admin\SystemMetric;

class AdminDashboardService
{
    public function getExecutiveSummary(): array
    {
        return [
            'total_users' => User::count(),
            'total_tenants' => Tenant::count(),
            'total_projects' => Project::count(),
            'mrr' => SystemMetric::where('metric_key', 'mrr')->latest('recorded_at')->value('value') ?? 0,
            'arr' => SystemMetric::where('metric_key', 'arr')->latest('recorded_at')->value('value') ?? 0,
            'churn_rate' => SystemMetric::where('metric_key', 'churn_rate')->latest('recorded_at')->value('value') ?? 0,
        ];
    }
}
