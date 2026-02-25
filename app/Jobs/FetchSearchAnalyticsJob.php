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

class FetchSearchAnalyticsJob implements ShouldQueue
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
            
            // We fetch the data for yesterday to ensure GSC has fully updated it
            $endDate = now()->subDays(2)->format('Y-m-d');
            $startDate = now()->subDays($retentionDays)->format('Y-m-d');

            Log::info("Fetching Search Analytics for project {$this->project->id} from {$startDate} to {$endDate}");

            // Note: Google recommends small chunks. For MVP, we pass the large date range. 
            // In Production, chunk by day: `for ($d = 2; $d <= $retentionDays; $d++)`
            $rows = $gscService->fetchSearchAnalytics($startDate, $endDate);

            $upsertData = [];
            foreach ($rows as $row) {
                // Dimensions order: ['query', 'page', 'device', 'country']
                $keys = $row->getKeys();
                
                $upsertData[] = [
                    'project_id' => $this->project->id,
                    'date' => $endDate, // Using endDate as approximation for this batch MVP
                    'query' => $keys[0] ?? null,
                    'page' => $keys[1] ?? null,
                    'device' => $keys[2] ?? null,
                    'country' => $keys[3] ?? null,
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
