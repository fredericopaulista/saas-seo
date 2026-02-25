<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\UrlStatus;
use App\Services\Google\SearchConsoleService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Spatie\Multitenancy\Jobs\TenantAware;
use Spatie\Multitenancy\Models\Tenant;

class FetchSitemapJob implements ShouldQueue, TenantAware
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
                // Process standard sitemaps
                try {
                    $sitemapUrl = $sitemap['path'];
                    $response = Http::timeout(15)->get($sitemapUrl);
                    
                    if ($response->successful()) {
                        $xml = simplexml_load_string($response->body());
                        
                        if ($xml !== false) {
                            $urls = [];
                            
                            // Handle standard <urlset>
                            if (isset($xml->url)) {
                                foreach ($xml->url as $urlNode) {
                                    $urls[] = (string) $urlNode->loc;
                                }
                            } 
                            // Handle sitemap indexes <sitemapindex>
                            else if (isset($xml->sitemap)) {
                                foreach ($xml->sitemap as $sitemapNode) {
                                    $subSitemapUrl = (string) $sitemapNode->loc;
                                    $subRes = Http::timeout(15)->get($subSitemapUrl);
                                    if ($subRes->successful()) {
                                        $subXml = simplexml_load_string($subRes->body());
                                        if ($subXml !== false && isset($subXml->url)) {
                                            foreach ($subXml->url as $urlNode) {
                                                $urls[] = (string) $urlNode->loc;
                                            }
                                        }
                                    }
                                }
                            }
                            
                            foreach ($urls as $url) {
                                if (filter_var($url, FILTER_VALIDATE_URL)) {
                                    // Make sure it doesn't overwrite index_status to keep historical data intact
                                    UrlStatus::firstOrCreate(
                                        [
                                            'project_id' => $this->project->id, 
                                            'url' => $url
                                        ],
                                        [
                                            'coverage_status' => 'Pending',
                                            'index_status' => 'Pending',
                                            'last_crawled' => now()->subDay(), // Force early check on next run
                                        ]
                                    );
                                }
                            }
                        }
                    }
                } catch (Exception $xmlErr) {
                    Log::error("Failed to parse sitemap XML for {$sitemapUrl}: " . $xmlErr->getMessage());
                }
            }

            Log::info("Sitemap fetch completed for project {$this->project->id}");

        } catch (Exception $e) {
            Log::error("Failed to fetch sitemaps for project {$this->project->id}: " . $e->getMessage());
        }
    }
}
