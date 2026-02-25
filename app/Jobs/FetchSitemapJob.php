<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\UrlStatus;
use App\Services\Google\SearchConsoleService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchSitemapJob implements ShouldQueue
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
            $sitemaps = $gscService->fetchSitemaps();

            foreach ($sitemaps as $sitemap) {
                // In a real app we would parse the sitemap XML itself to get all URLs.
                // Here we just save the sitemap URL itself as a discovered URL for MVP scope.
                
                UrlStatus::updateOrCreate(
                    [
                        'project_id' => $this->project->id, 
                        'url' => $sitemap['path']
                    ],
                    [
                        'coverage_status' => $sitemap['errors'] > 0 ? 'Error' : 'Valid',
                        'last_crawled' => $sitemap['last_submitted'] ? \Carbon\Carbon::parse($sitemap['last_submitted']) : now(),
                    ]
                );
            }

            Log::info("Sitemap fetch completed for project {$this->project->id}");

        } catch (Exception $e) {
            Log::error("Failed to fetch sitemaps for project {$this->project->id}: " . $e->getMessage());
        }
    }
}
