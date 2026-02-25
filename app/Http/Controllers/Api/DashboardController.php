<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Jobs\SyncProjectDataJob;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get high-level overview of the selected project (SEO Score, URL counts).
     */
    public function overview(Request $request, Project $project)
    {
        // Add auth check here if using Sanctum
        // Gate::authorize('view', $project);

        $latestScore = $project->seoScores()->latest()->first();

        $urlStats = [
            'total' => $project->urlStatuses()->count(),
            'valid' => $project->urlStatuses()->where('coverage_status', 'Valid')->count(),
            'error' => $project->urlStatuses()->where('coverage_status', 'Error')->count(),
            'indexed' => $project->urlStatuses()->where('index_status', 'Indexed')->count(),
        ];

        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'domain' => $project->domain,
            ],
            'seo_score' => $latestScore,
            'url_stats' => $urlStats,
        ]);
    }

    /**
     * Get performance metrics across a date range.
     */
    public function performance(Request $request, Project $project)
    {
        // Default to last 30 days if no dates provided
        $startDate = $request->query('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        // Grouping by date for chart data
        $dailyMetrics = $project->performanceData()
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('date, SUM(clicks) as total_clicks, SUM(impressions) as total_impressions, AVG(ctr) as avg_ctr, AVG(position) as avg_position')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Totals for scorecard
        $totals = [
            'clicks' => $dailyMetrics->sum('total_clicks'),
            'impressions' => $dailyMetrics->sum('total_impressions'),
            'avg_ctr' => $dailyMetrics->avg('avg_ctr'),
            'avg_position' => $dailyMetrics->avg('avg_position'),
        ];

        return response()->json([
            'date_range' => ['start' => $startDate, 'end' => $endDate],
            'totals' => $totals,
            'chart_data' => $dailyMetrics,
        ]);
    }

    /**
     * Get the latest actionable insights for the project.
     */
    public function insights(Request $request, Project $project)
    {
        // Only fetch unresolved insights
        $insights = $project->insights()
            ->whereNull('resolved_at')
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'insights' => $insights
        ]);
    }

    /**
     * Manually dispatches the Google Search Console sync job.
     */
    public function triggerSync(Request $request, Project $project)
    {
        // Add auth constraints here based on Tenant
        SyncProjectDataJob::dispatch($project);

        return response()->json([
            'message' => 'Sincronização agendada na fila com sucesso. Os dados devem aparecer em alguns minutos.'
        ]);
    }
}
