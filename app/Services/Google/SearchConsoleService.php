<?php

namespace App\Services\Google;

use App\Models\Project;
use App\Models\SearchConsoleToken;
use Exception;
use Google\Client as GoogleClient;
use Google\Service\Webmasters as GoogleWebmasters;
use Google\Service\SearchConsole;
use Illuminate\Support\Facades\Log;

class SearchConsoleService
{
    protected GoogleClient $client;
    protected ?Project $project = null;

    public function __construct()
    {
        $this->client = new GoogleClient();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setAccessType('offline');
        $this->client->addScope('https://www.googleapis.com/auth/webmasters.readonly');
    }

    /**
     * Set the active project and configure the Google Client with its token.
     */
    public function setProject(Project $project): self
    {
        $this->project = $project;
        $token = $project->searchConsoleToken;

        if (! $token) {
            throw new Exception("No Search Console token found for project {$project->id}");
        }

        $this->client->setAccessToken([
            'access_token' => $token->access_token,
            'refresh_token' => $token->refresh_token,
            'expires_in' => $token->expires_in,
        ]);

        // Auto-refresh token if expired
        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $ret = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                if (isset($ret['error'])) {
                    throw new Exception("Could not refresh token: " . json_encode($ret));
                }

                // Update token in DB
                $token->update([
                    'access_token' => $this->client->getAccessToken()['access_token'],
                    'expires_in' => $this->client->getAccessToken()['expires_in'],
                ]);
            } else {
                throw new Exception("Token is expired and no refresh token is available.");
            }
        }

        return $this;
    }

    /**
     * Returns an instance of the Webmasters Service.
     */
    public function getWebmastersService(): GoogleWebmasters
    {
        return new GoogleWebmasters($this->client);
    }

    /**
     * List all sites/properties available to the connected user.
     */
    public function listProperties(): array
    {
        try {
            $service = $this->getWebmastersService();
            $sites = $service->sites->listSites();
            $properties = [];

            foreach ($sites->getSiteEntry() as $site) {
                $properties[] = [
                    'url' => $site->getSiteUrl(),
                    'permission_level' => $site->getPermissionLevel(),
                ];
            }

            return $properties;
        } catch (Exception $e) {
            Log::error("Error listing properties: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch Search Analytics from GSC API.
     */
    public function fetchSearchAnalytics(string $startDate, string $endDate, array $dimensions = ['query', 'page', 'device', 'country'], int $rowLimit = 25000): array
    {
        try {
            $service = $this->getWebmastersService();
            $property = $this->project->gsc_property;

            $request = new \Google\Service\Webmasters\SearchAnalyticsQueryRequest();
            $request->setStartDate($startDate);
            $request->setEndDate($endDate);
            $request->setDimensions($dimensions);
            $request->setRowLimit($rowLimit);

            $response = $service->searchanalytics->query($property, $request);
            
            return $response->getRows() ?? [];

        } catch (Exception $e) {
            Log::error("Error fetching Search Analytics for project {$this->project->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch Sitemaps for the current project.
     */
    public function fetchSitemaps(): array
    {
        try {
            $service = $this->getWebmastersService();
            $property = $this->project->gsc_property;
            
            $response = $service->sitemaps->listSitemaps($property);
            
            $sitemaps = [];
            foreach ($response->getSitemap() as $sitemap) {
                $sitemaps[] = [
                    'path' => $sitemap->getPath(),
                    'last_submitted' => $sitemap->getLastSubmitted(),
                    'is_pending' => $sitemap->getIsPending(),
                    'is_sitemaps_index' => $sitemap->getIsSitemapsIndex(),
                    'type' => $sitemap->getType(),
                    'warnings' => $sitemap->getWarnings(),
                    'errors' => $sitemap->getErrors(),
                ];
            }

            return $sitemaps;

        } catch (Exception $e) {
            Log::error("Error fetching Sitemaps for project {$this->project->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Inspect a specific URL indexation status
     */
    public function inspectUrl(string $url): ?\Google\Service\SearchConsole\InspectUrlIndexResponse
    {
        try {
            $service = new SearchConsole($this->client);
            $property = $this->project->gsc_property;

            $request = new \Google\Service\SearchConsole\InspectUrlIndexRequest();
            $request->setInspectionUrl($url);
            $request->setSiteUrl($property);

            return $service->urlInspection_index->inspect($request);

        } catch (Exception $e) {
            Log::error("Error inspecting URL {$url} for project {$this->project->id}: " . $e->getMessage());
            return null;
        }
    }
}

