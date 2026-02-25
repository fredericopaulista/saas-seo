<?php

namespace App\Services\Insights;

use App\Models\Project;
use App\Models\Insight;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use OpenAI;

class InsightsEngine
{
    /**
     * Executes the main insight rules for a project using AI reasoning via OpenAI.
     */
    public function analyze(Project $project): void
    {
        $openAiKey = Setting::where('key', 'OPENAI_API_KEY')->value('value');

        if (!$openAiKey) {
            Log::warning("Insights Engine falhou para o projeto {$project->id}. O Super Admin ainda não configurou a OPENAI_API_KEY.");
            return;
        }

        // Fetch top low hanging fruits for analysis (e.g. pages close to page 1 but losing CTR)
        $opportunities = $project->performanceData()
            ->whereBetween('position', [5, 25])
            ->where('impressions', '>', 50)
            ->latest('date')
            ->take(20)
            ->get();

        if ($opportunities->isEmpty()) {
            return;
        }

        $dataPayload = $opportunities->map(function($data) {
            return [
                'query' => $data->query,
                'page' => $data->page,
                'clicks' => $data->clicks,
                'impressions' => $data->impressions,
                'ctr' => $data->ctr,
                'position' => $data->position
            ];
        })->toJson();

        $prompt = <<<EOT
You are a senior technical SEO expert. Analyzing the following Google Search Console recent metric snapshot for domain "{$project->domain}", 
your job is to identify actionable "Insights". Look for:
1. "opportunity": Queries ranking between position 8 and 25 with good impressions but bad CTR. Identify exact steps to refine their metadata or internal linking.
2. "anomaly": Severe drop in metrics or highly weird CTR for its ranking. 

Return only a pure JSON array containing the insights found, following this exact schema per object:
[
  {
    "type": "opportunity or anomaly",
    "severity": "Low, Medium, or High",
    "title": "A short, direct impact title (in Portuguese)",
    "description": "The detailed explanation of what is happening and the actionable recommendation to fix it (in Portuguese)",
    "metadata": {
      "query": "the affected query if applicable",
      "metric_focus": "e.g. position, ctr, etc."
    }
  }
]

Maximum 3 most critical insights. JSON only (without ```json wrappers).
DATA SNAPSHOT:
$dataPayload
EOT;

        try {
            $client = OpenAI::client($openAiKey);

            $response = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an SEO AI specialized in analyzing Google Search data.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.4,
            ]);
            
            Log::info("Insights Engine: Successfully received response from OpenAI for project {$project->id}");

            $content = trim($response->choices[0]->message->content);
            
            // Extract the JSON array using regex in case OpenAI adds conversational text or markdown code blocks
            preg_match('/\[.*\]/s', $content, $matches);
            
            if (isset($matches[0])) {
                $parsedInsights = json_decode($matches[0], true);
            } else {
                // Fallback to decode the whole content if regex fails
                $parsedInsights = json_decode($content, true);
            }
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Insights Engine JSON Decode Error for project {$project->id}: " . json_last_error_msg());
                Log::error("Raw OpenAI output was: " . $content);
            }

            if (is_array($parsedInsights)) {
                foreach ($parsedInsights as $insightData) {
                    
                    // Prevent duplicating exact titles for the same URL target
                    $exists = Insight::where('project_id', $project->id)
                        ->where('title', $insightData['title'])
                        ->exists();

                    if (!$exists) {
                        Insight::create([
                            'project_id' => $project->id,
                            'type' => $insightData['type'] ?? 'opportunity',
                            'severity' => $insightData['severity'] ?? 'Medium',
                            'title' => $insightData['title'],
                            'description' => $insightData['description'],
                            'metadata' => $insightData['metadata'] ?? [],
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error("Failed to generate AI insights for project {$project->id}: " . $e->getMessage());
        }
    }
}
