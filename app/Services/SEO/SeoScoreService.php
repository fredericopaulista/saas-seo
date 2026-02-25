<?php

namespace App\Services\SEO;

use App\Models\Project;
use App\Models\SeoScore;

class SeoScoreService
{
    /**
     * Calculates and persists a new Score for the given project based on its recent analytical data.
     */
    public function generateScore(Project $project): SeoScore
    {
        // 1. Calculate Technical Score (based on UrlStatus)
        $technicalScore = $this->calculateTechnicalScore($project);

        // 2. Calculate Performance Score (based on PerformanceData metrics)
        $performanceScore = $this->calculatePerformanceScore($project);

        // 3. Calculate Indexing Score (based on total indexed vs non-indexed)
        $indexScore = $this->calculateIndexScore($project);

        // Overall Score (Weighted average)
        // Technical: 40%, Performance: 40%, Index: 20%
        $overallScore = (int) round(($technicalScore * 0.4) + ($performanceScore * 0.4) + ($indexScore * 0.2));

        return SeoScore::create([
            'project_id' => $project->id,
            'score' => $overallScore,
            'technical_score' => $technicalScore,
            'performance_score' => $performanceScore,
            'index_score' => $indexScore,
        ]);
    }

    private function calculateTechnicalScore(Project $project): int
    {
        $totalUrls = $project->urlStatuses()->count();
        if ($totalUrls === 0) return 0;

        $validUrls = $project->urlStatuses()->where('coverage_status', 'Valid')->count();
        $errorUrls = $project->urlStatuses()->where('coverage_status', 'Error')->count();

        // Base 100
        $score = 100;
        
        // Deduct 5 points for every error relative to 1% of total
        $errorRatio = $errorUrls / $totalUrls;
        $score -= ($errorRatio * 500); // Ex: 10% errors = -50 pts

        return (int) max(0, min(100, $score));
    }

    private function calculatePerformanceScore(Project $project): int
    {
        // Compare last 7 days vs previous 7 days (mocked logic for simplicity)
        $recentClicks = $project->performanceData()
            ->where('date', '>=', now()->subDays(7))
            ->sum('clicks');

        if ($recentClicks > 0) {
            return 85; // A real implementation would extrapolate delta percentage
        }

        return 50; 
    }

    private function calculateIndexScore(Project $project): int
    {
        $totalUrls = $project->urlStatuses()->count();
        if ($totalUrls === 0) return 0;

        $indexedUrls = $project->urlStatuses()->where('index_status', 'Indexed')->count();
        
        $ratio = $indexedUrls / $totalUrls;

        return (int) round($ratio * 100);
    }
}
