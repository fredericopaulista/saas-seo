<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MetricsRepository;

class SystemMonitoringService
{
    /**
     * Get an overview of system health
     */
    public function getSystemHealth(): array
    {
        return [
            'redis' => $this->getRedisMetrics(),
            'queues' => $this->getQueueMetrics(),
            'horizon' => $this->getHorizonStatus(),
            'gsc_quota' => $this->getGscQuotaEstimation()
        ];
    }

    private function getRedisMetrics(): array
    {
        try {
            if (config('database.redis.client') === 'mock' || !class_exists('Redis')) {
                throw new \Exception('Mocked Redis');
            }
            $info = Redis::info();
            return [
                'status' => 'online',
                'memory_used' => $info['used_memory_human'] ?? '32M',
                'connected_clients' => $info['connected_clients'] ?? 1,
                'uptime_days' => $info['uptime_in_days'] ?? 1,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline', 
                'memory_used' => 'N/A',
                'connected_clients' => 0,
                'uptime_days' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    private function getQueueMetrics(): array
    {
        try {
            return [
                'default_pending' => Queue::size('default') ?? 0,
                'seo_pending' => Queue::size('seo_metrics') ?? 0,
                'failed_jobs' => \Illuminate\Support\Facades\DB::table('failed_jobs')->count(),
            ];
        } catch (\Exception $e) {
             return [
                'default_pending' => 0,
                'seo_pending' => 0,
                'failed_jobs' => 0,
            ];
        }
    }

    private function getHorizonStatus(): array
    {
        // A naive check if Master Supervisors exist marking Horizon as online
        if (class_exists(MasterSupervisorRepository::class)) {
             try {
                 $supervisors = app(MasterSupervisorRepository::class)->all();
                 return [
                     'status' => !empty($supervisors) ? 'running' : 'inactive',
                     'active_supervisors' => count($supervisors)
                 ];
             } catch (\Exception $e) {
                 return ['status' => 'error', 'message' => 'Horizon setup issue'];
             }
        }
        return ['status' => 'not_installed'];
    }

    private function getGscQuotaEstimation(): array
    {
        // Mocked estimation logic for phase 1
        $totalProjects = \App\Models\Project::count();
        // Assuming ~5 calls per project daily
        $estimatedDailyCalls = $totalProjects * 5; 
        
        return [
            'total_projects' => $totalProjects,
            'estimated_daily_calls' => $estimatedDailyCalls,
            'status' => $estimatedDailyCalls > 10000 ? 'warning' : 'healthy' // GSC limit is high, warning just for UX
        ];
    }
}
