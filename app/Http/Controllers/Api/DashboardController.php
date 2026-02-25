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
        
        // Fetch previous period for percentage calculation
        $daysDiff = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) ?: 30;
        $prevStartDate = \Carbon\Carbon::parse($startDate)->subDays($daysDiff)->toDateString();
        $prevEndDate = \Carbon\Carbon::parse($startDate)->subDay()->toDateString();
        
        $prevMetrics = $project->performanceData()
            ->whereBetween('date', [$prevStartDate, $prevEndDate])
            ->selectRaw('SUM(clicks) as total_clicks')
            ->first();
            
        $prevClicks = $prevMetrics ? $prevMetrics->total_clicks : 0;
        $clickDiffPercentage = 0;
        if ($prevClicks > 0) {
            $clickDiffPercentage = (($totals['clicks'] - $prevClicks) / $prevClicks) * 100;
        } elseif ($totals['clicks'] > 0) {
            $clickDiffPercentage = 100; // From 0 to something is 100% growth essentially
        }
        
        $totals['click_diff_percentage'] = round($clickDiffPercentage, 1);

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
     * Mark an insight as resolved.
     */
    public function resolveInsight(Request $request, Project $project, \App\Models\Insight $insight)
    {
        // Add auth check here if using Sanctum
        if ($insight->project_id !== $project->id) {
            return response()->json(['error' => 'Insight does not belong to this project'], 403);
        }

        $insight->update([
            'resolved_at' => now()
        ]);

        return response()->json([
            'message' => 'Tarefa marcada como resolvida com sucesso.'
        ]);
    }
    
    /**
     * Get paginated URLs for the project.
     */
    public function urls(Request $request, Project $project)
    {
        $urls = $project->urlStatuses()
            ->orderBy('id', 'desc')
            ->paginate(50);
            
        return response()->json($urls);
    }

    /**
     * Manually dispatches the Google Search Console sync job.
     */
    public function triggerSync(Request $request, Project $project)
    {
        // Add auth constraints here based on Tenant
        if ($project->tenant) {
            $project->tenant->makeCurrent();
        }

        // Pre-flight check to see if the token is valid before dispatching jobs
        // that would otherwise silently swallow the error in the logs
        try {
            $gscService = app(\App\Services\Google\SearchConsoleService::class);
            $gscService->setProject($project);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Token is expired') || str_contains($e->getMessage(), 'No Search Console token found')) {
                return response()->json(['error' => 'Sua conexão com Google expirou ou é inválida. Por favor, conecte novamente sua conta.'], 401);
            }
            return response()->json(['error' => 'Erro de conexão: ' . $e->getMessage()], 500);
        }

        try {
            SyncProjectDataJob::dispatch($project);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro interno ao processar sincronização: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Sincronização agendada na fila com sucesso. Os dados devem aparecer em alguns minutos.'
        ]);
    }

    /**
     * Manually dispatches the Google URL Inspection job.
     */
    public function triggerUrlInspection(Request $request, Project $project)
    {
        if ($project->tenant) {
            $project->tenant->makeCurrent();
        }

        try {
            \App\Jobs\InspectUrlsJob::dispatch($project);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro interno ao iniciar inspeção: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Verificação de URLs agendada na fila com sucesso. A tabela será atualizada em breve.'
        ]);
    }
}
