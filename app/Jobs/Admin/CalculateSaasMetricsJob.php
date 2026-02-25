<?php

namespace App\Jobs\Admin;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Admin\SystemMetric;

class CalculateSaasMetricsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Simple MRR heuristic for phase 1
        $mrr = Tenant::count() * 99; // Example metric logic

        SystemMetric::create([
            'metric_key' => 'mrr',
            'value' => $mrr,
            'recorded_at' => now(),
        ]);

        SystemMetric::create([
            'metric_key' => 'total_users',
            'value' => User::count(),
            'recorded_at' => now(),
        ]);

        SystemMetric::create([
            'metric_key' => 'total_tenants',
            'value' => Tenant::count(),
            'recorded_at' => now(),
        ]);
    }
}
