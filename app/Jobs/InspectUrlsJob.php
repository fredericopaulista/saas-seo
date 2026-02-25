<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\UrlStatus;
use App\Models\Insight;
use App\Services\Google\SearchConsoleService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Jobs\TenantAware;

class InspectUrlsJob implements ShouldQueue, TenantAware
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
    public function handle(SearchConsoleService $gscService): void
    {
        try {
            $gscService->setProject($this->project);
            
            // Get URLs that are pending indexation check or haven't been crawled in 7 days
            $urlsToInspect = UrlStatus::where('project_id', $this->project->id)
                ->orderBy('last_crawled', 'asc') // Oldest first
                ->take(50) // GSC has rate limits, handle small chunk per job execution
                ->get();
                
            Log::info("Starting URL Inspection for " . $urlsToInspect->count() . " URLs on project {$this->project->id}");

            foreach ($urlsToInspect as $urlStatus) {
                $response = $gscService->inspectUrl($urlStatus->url);
                
                if ($response && $response->getInspectionResult()) {
                    $indexResult = $response->getInspectionResult()->getIndexStatusResult();
                    $verdict = $indexResult->getVerdict();
                    $coverageState = $indexResult->getCoverageState();
                    // verdicts usually: PASS, PARTIAL, FAIL, NEUTRAL.
                    
                    $status = ($verdict === 'PASS') ? 'Indexed' : 'Error';
                    if (str_contains($coverageState, 'Discovered')) {
                        $status = 'Discovered';
                    }
                    
                    $urlStatus->update([
                        'index_status' => $status,
                        'coverage_status' => $coverageState,
                        'last_crawled' => now()
                    ]);
                    
                    // If URL is not indexed, automatically create a high priority Task Insight
                    if ($status !== 'Indexed') {
                        $insightTitle = "Página não Indexada: " . parse_url($urlStatus->url, PHP_URL_PATH);
                        
                        $exists = Insight::where('project_id', $this->project->id)
                            ->where('title', $insightTitle)
                            ->exists();

                        if (!$exists) {
                            Insight::create([
                                'project_id' => $this->project->id,
                                'type' => 'anomaly',
                                'severity' => 'High',
                                'title' => $insightTitle,
                                'description' => "A página {$urlStatus->url} não está indexada no Google. Status atual: {$coverageState}. Recomenda-se forçar a indexação manualmente no Search Console e verificar se há bloqueios.",
                            ]);
                        }
                    }
                }
                
                // Sleep to avoid rate limiting max QPS
                usleep(500000); 
            }

            Log::info("URL Inspection completed for project {$this->project->id}");

        } catch (Exception $e) {
            Log::error("Failed to inspect URLs for project {$this->project->id}: " . $e->getMessage());
        }
    }
}
