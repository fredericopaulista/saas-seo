<?php

namespace App\Jobs;

use App\Models\Project;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Jobs\TenantAware;
use Spatie\Multitenancy\Models\Tenant;

class SyncProjectDataJob implements ShouldQueue, TenantAware
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
    public function handle(): void
    {
        Log::info("Starting background sync for project {$this->project->id}");

        // 1. Dispatch Sitemaps Fetcher Job
        FetchSitemapJob::dispatch($this->project);

        // 2. Dispatch Search Analytics Fetcher Job
        FetchSearchAnalyticsJob::dispatch($this->project);

        // 3. Dispatch SEO Score Recalculation (ideally this runs after the ones above, using chain or batch)
        // For MVP simplicity, we dispatch it to run delayed, assuming the previous jobs finished.
        GenerateSeoScoreJob::dispatch($this->project)->delay(now()->addMinutes(5));
    }
}
