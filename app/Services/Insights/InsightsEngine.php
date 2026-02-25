<?php

namespace App\Services\Insights;

use App\Models\Project;
use App\Models\Insight;

class InsightsEngine
{
    /**
     * Executes the main insight rules for a project and records anomalies/opportunities.
     */
    public function analyze(Project $project): void
    {
        $this->detectTrafficDrops($project);
        $this->detectLowHangingFruits($project);
    }

    /**
     * Identifies pages that lost more than 20% of clicks compared to previous period.
     */
    private function detectTrafficDrops(Project $project): void
    {
        // Example logic: in real app you aggregate using advanced SQL queries
        // comparing date range X against date range Y.
        
        // Simulating the rule:
        $hasSignificantDrop = false; // pseudo logic check
        
        if ($hasSignificantDrop) {
            Insight::create([
                'project_id' => $project->id,
                'type' => 'anomaly',
                'severity' => 'CRITICAL',
                'title' => 'Severe Traffic Drop Detected',
                'description' => 'The path /example-page lost 25% of its organic clicks over the past 7 days.',
                'metadata' => [
                    'page' => '/example-page',
                    'drop_percentage' => 25.0,
                    'previous_clicks' => 1000,
                    'current_clicks' => 750,
                ],
            ]);
        }
    }

    /**
     * Identifies queries ranking between 8 and 20 that generate high impressions but low CTR.
     */
    private function detectLowHangingFruits(Project $project): void
    {
        // Fetch queries from DB that match the criteria
        $opportunities = $project->performanceData()
            ->whereBetween('position', [8, 20])
            ->where('impressions', '>', 500)
            ->where('ctr', '<', 2.0)
            ->get();

        foreach ($opportunities as $opp) {
            // Prevent duplicate insights creation
            $exists = $project->insights()
                ->where('type', 'opportunity')
                ->whereJsonContains('metadata->query', $opp->query)
                ->whereNull('resolved_at')
                ->exists();

            if (! $exists) {
                Insight::create([
                    'project_id' => $project->id,
                    'type' => 'opportunity',
                    'severity' => 'INFO',
                    'title' => 'Optimization Opportunity Found',
                    'description' => "Query '{$opp->query}' is ranking at position {$opp->position} with high impressions but low CTR. Title/Meta optimization recommended.",
                    'metadata' => [
                        'query' => $opp->query,
                        'position' => $opp->position,
                        'impressions' => $opp->impressions,
                        'ctr' => $opp->ctr,
                    ],
                ]);
            }
        }
    }
}
