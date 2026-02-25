<?php

namespace App\Jobs;

use App\Models\PerformanceData;
use App\Models\Project;
use App\Services\Billing\BillingService;
use App\Services\Google\SearchConsoleService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Jobs\TenantAware;
use Spatie\Multitenancy\Models\Tenant;

class FetchSearchAnalyticsJob implements ShouldQueue, TenantAware
{
    use Queueable;

    protected Project $project;

    /**
     * Create a new job instance.
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * Execute the job.
     */
    public function handle(SearchConsoleService $gscService, BillingService $billingService): void
    {
        try {
            $gscService->setProject($this->project);
            
            // Determine how many days back to fetch based on Tenant plan limits
            $retentionDays = $billingService->getGscRetentionDays($this->project->tenant);
            
            // Since we need percentage growth, let's fetch double the retention days just the first time, or just 60 days flat for MVP to work:
            $startDate = now()->subDays(60)->format('Y-m-d');
            $endDate = now()->subDays(2)->format('Y-m-d'); // GSC data delay is typically ~2 days

            Log::info("Fetching Search Analytics for project {$this->project->id} from {$startDate} to {$endDate}");

            // Ensure we include 'date' in the dimensions so we get rows segregated by date
            $dimensions = ['date', 'query', 'page', 'device', 'country'];
            $rows = $gscService->fetchSearchAnalytics($startDate, $endDate, $dimensions);
            
            Log::info("GSC API returned " . count($rows) . " rows for project {$this->project->id}");

            $upsertData = [];
            foreach ($rows as $row) {
                $keys = $row->getKeys();
                // Dimensions order mapping based on $dimensions array: 
                // 0: date, 1: query, 2: page, 3: device, 4: country
                
                $upsertData[] = [
                    'project_id' => $this->project->id,
                    'date' => $keys[0] ?? $endDate, // Safe fallback
                    'query' => $keys[1] ?? null,
                    'page' => $keys[2] ?? null,
                    'device' => $keys[3] ?? null,
                    'country' => $keys[4] ?? null,
                    'clicks' => $row->getClicks(),
                    'impressions' => $row->getImpressions(),
                    'ctr' => $row->getCtr() * 100,
                    'position' => $row->getPosition(),
                ];
            }

            // Chunk inserts for performance (batch limit in SQL is around 65K placeholders)
            foreach (array_chunk($upsertData, 1000) as $chunk) {
                PerformanceData::upsert(
                    $chunk,
                    ['project_id', 'date', 'query', 'page', 'device', 'country'],
                    ['clicks', 'impressions', 'ctr', 'position']
                );
            }

            Log::info("Successfully fetched and stored Search Analytics for project {$this->project->id}");

        } catch (Exception $e) {
            Log::error("Failed to fetch Search Analytics for project {$this->project->id}: " . $e->getMessage());
        }
    }
}
