<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\SearchConsoleToken;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Multitenancy\Models\Tenant;
use Exception;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    /**
     * Redirects the user to Google's OAuth consent screen.
     */
    public function redirect(Request $request)
    {
        // Validating that a project ID is passed so we know what to attach the token to
        $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
        ]);

        $project = Project::findOrFail($request->project_id);

        // Keep the project ID and its tenant ID in session or state to use after callback
        $state = json_encode(['project_id' => $project->id, 'tenant_id' => $project->tenant_id]);

        return Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/webmasters.readonly'])
            ->with([
                'access_type' => 'offline', 
                'prompt' => 'consent',
                'state' => $state
            ])
            ->stateless()
            ->redirect();
    }

    /**
     * Handles the callback from Google, capturing tokens.
     */
    public function callback(Request $request)
    {
        try {
            if ($request->has('error')) {
                return response()->json(['error' => 'User denied the request or an error occurred during Google OAuth.'], 403);
            }

            // Restore state manually or from request
            $stateData = json_decode($request->state, true);
            $projectId = $stateData['project_id'] ?? null;
            $tenantId = $stateData['tenant_id'] ?? null;

            if (! $projectId || ! $tenantId) {
                return response()->json(['error' => 'Invalid state. Project ID or Tenant ID missing.'], 400);
            }

            // Temporarily set the tenant for this stateless request to save the token properly
            Tenant::find($tenantId)?->makeCurrent();

            $googleUser = Socialite::driver('google')->stateless()->user();

            $project = Project::find($projectId);

            if (! $project) {
                return response()->json(['error' => 'Project not found.'], 404);
            }

            // Encrypting tokens and storing
            SearchConsoleToken::updateOrCreate(
                ['project_id' => $project->id],
                [
                    'access_token' => $googleUser->token,
                    // The refresh token is only provided on the first authorization (prompt=consent)
                    'refresh_token' => $googleUser->refreshToken ?? SearchConsoleToken::where('project_id', $project->id)->value('refresh_token'),
                    'expires_in' => $googleUser->expiresIn,
                ]
            );

            return response()->json([
                'message' => 'Google Search Console account connected successfully.',
                'project_id' => $project->id
            ], 200);

        } catch (Exception $e) {
            Log::error('Google OAuth Callback Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'An error occurred while connecting the account.'], 500);
        }
    }
}
