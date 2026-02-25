<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Redis;

class SystemMonitoringService
{
    public function getQueueMetrics(): array
    {
        // Sample queue length monitoring (Assuming default queues config)
        return [
            'default_queue_length' => Redis::llen('queues:default') ?? 0,
            'high_queue_length' => Redis::llen('queues:high') ?? 0,
        ];
    }
    
    public function getRedisUsage(): array
    {
        $info = Redis::info('memory');
        return [
            'used_memory_human' => $info['used_memory_human'] ?? '0M',
        ];
    }
}
