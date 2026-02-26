<?php

namespace App\Http\Controllers\Api\Webhook;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\WebhookEvent;
use App\Jobs\ProcessAsaasWebhookJob;
use App\Services\Billing\AsaasGatewayService;

class AsaasWebhookController extends Controller
{
    /**
     * Handle incoming webhooks from Asaas.
     *
     * Strategy (per Asaas best practices):
     *   1. Validate the token — reject immediately if invalid (HTTP 401)
     *   2. Check idempotency — if already processed, confirm with HTTP 200
     *   3. Dispatch async Job — return HTTP 200 immediately
     *   4. Process asynchronously — queue handles the business logic
     */
    public function handle(Request $request)
    {
        // ── 1. Token Validation ──────────────────────────────────────────────
        // Token is managed in the Super Admin panel → stored encrypted in DB
        $expectedToken = AsaasGatewayService::getWebhookToken();

        // Only enforce validation if a token is configured
        if (!empty($expectedToken)) {
            $receivedToken = $request->header('asaas-access-token');
            if ($receivedToken !== $expectedToken) {
                Log::warning('Asaas Webhook: Unauthorized request — invalid token.', [
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        // ── 2. Extract Event Data ────────────────────────────────────────────
        $eventId   = $request->input('id');
        $eventType = $request->input('event');
        $payload   = $request->all();

        // Ignore malformed payloads
        if (!$eventId || !$eventType) {
            Log::warning('Asaas Webhook: Malformed payload received.', ['body' => $payload]);
            return response()->json(['status' => 'ignored', 'reason' => 'missing event id or type'], 400);
        }

        Log::info("Asaas Webhook received: [{$eventType}]", ['event_id' => $eventId]);

        // ── 3. Idempotency Check ─────────────────────────────────────────────
        // Asaas guarantees "at-least-once" delivery, so duplicates can arrive.
        // We check quickly here — the Job also checks before any DB writes.
        if (WebhookEvent::alreadyProcessed($eventId)) {
            return response()->json(['status' => 'already_processed']);
        }

        // ── 4. Dispatch Async Job ────────────────────────────────────────────
        ProcessAsaasWebhookJob::dispatch($eventId, $eventType, $payload);

        // Return 200 immediately — Asaas will mark notification as successful.
        return response()->json(['status' => 'queued']);
    }
}
