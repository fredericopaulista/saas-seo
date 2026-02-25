<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\Insights\InsightsEngine;
use App\Services\SEO\SeoScoreService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Jobs\TenantAware;
use Spatie\Multitenancy\Models\Tenant;

class GenerateSeoScoreJob implements ShouldQueue, TenantAware
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
    public function handle(SeoScoreService $seoService, InsightsEngine $insightsEngine): void
    {
        try {
            Log::info("Generating SEO Score and Insights for project {$this->project->id}");

            // 1. Generate new SEO Score record
            $seoService->generateScore($this->project);

            // 2. Run Insights rules engine
            $insightsEngine->analyze($this->project);

            Log::info("Successfully generated SEO metrics for project {$this->project->id}");

        } catch (Exception $e) {
            Log::error("Failed to generate SEO Score for project {$this->project->id}: " . $e->getMessage());
        }
    }
}
