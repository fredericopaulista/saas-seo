<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Models\WebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAsaasWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30; // seconds between retries

    public function __construct(
        protected string $eventId,
        protected string $eventType,
        protected array  $payload
    ) {}

    public function handle(): void
    {
        // --- Idempotency guard ------------------------------------------------
        // Another job may have already processed this event (at-least-once delivery)
        if (WebhookEvent::alreadyProcessed($this->eventId)) {
            Log::info("Asaas Webhook [{$this->eventType}] already processed, skipping.", [
                'event_id' => $this->eventId,
            ]);
            return;
        }

        // Record the event as pending before processing
        $record = WebhookEvent::firstOrCreate(
            ['event_id' => $this->eventId],
            [
                'source'     => 'asaas',
                'event_type' => $this->eventType,
                'payload'    => $this->payload,
                'status'     => 'pending',
            ]
        );

        try {
            $this->process($record);
            $record->update(['status' => 'processed', 'processed_at' => now()]);
        } catch (\Throwable $e) {
            $record->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::error("Asaas Webhook [{$this->eventType}] processing failed.", [
                'event_id' => $this->eventId,
                'error'    => $e->getMessage(),
            ]);
            throw $e; // re-throw so the queue retries
        }
    }

    // -------------------------------------------------------------------------

    protected function process(WebhookEvent $record): void
    {
        $payment      = $this->payload['payment']      ?? null;
        $subscription = $this->payload['payment']['subscription'] ?? null;

        Log::info("Asaas Webhook [{$this->eventType}]", [
            'event_id'       => $this->eventId,
            'subscription_id' => $subscription,
            'payment_id'     => $payment['id'] ?? null,
        ]);

        switch ($this->eventType) {

            // ---- Payment Events ----

            case 'PAYMENT_RECEIVED':
            case 'PAYMENT_CONFIRMED':
                $this->updateBySubscription($subscription, 'ACTIVE', 'active');
                break;

            case 'PAYMENT_OVERDUE':
                // Payment is late — restrict access but don't cancel yet
                $this->updateBySubscription($subscription, 'OVERDUE', 'past_due');
                break;

            case 'PAYMENT_REFUNDED':
            case 'PAYMENT_REFUND_CONFIRMED':
                $this->updateBySubscription($subscription, 'REFUNDED', 'canceled');
                break;

            case 'PAYMENT_DELETED':
            case 'PAYMENT_RESTORED':
                // Ignore — Asaas can delete and restore; only act on confirmed events
                $record->update(['status' => 'ignored']);
                return;

            case 'PAYMENT_CHARGEBACK_REQUESTED':
            case 'PAYMENT_CHARGEBACK_DISPUTE':
                // Log for manual review, restrict access
                Log::warning("Chargeback event received [{$this->eventType}]", [
                    'event_id'   => $this->eventId,
                    'payment_id' => $payment['id'] ?? null,
                ]);
                $this->updateBySubscription($subscription, 'CHARGEBACK', 'past_due');
                break;

            // ---- Subscription Events ----

            case 'SUBSCRIPTION_DELETED':
                $this->updateBySubscription($subscription, 'CANCELED', 'canceled');
                break;

            // ---- Unknown / Unhandled Events ----

            default:
                Log::debug("Asaas Webhook [{$this->eventType}] not handled.", [
                    'event_id' => $this->eventId,
                ]);
                $record->update(['status' => 'ignored']);
                return;
        }
    }

    // -------------------------------------------------------------------------

    protected function updateBySubscription(
        ?string $asaasSubscriptionId,
        string  $gatewayStatus,
        string  $localStatus
    ): void {
        if (!$asaasSubscriptionId) {
            Log::warning("Asaas Webhook [{$this->eventType}]: no subscription ID in payload.");
            return;
        }

        $sub = Subscription::where('asaas_subscription_id', $asaasSubscriptionId)->first();

        if (!$sub) {
            Log::warning("Asaas Webhook [{$this->eventType}]: subscription not found locally.", [
                'asaas_subscription_id' => $asaasSubscriptionId,
            ]);
            return;
        }

        $sub->update([
            'status_gateway' => $gatewayStatus,
            'status'         => $localStatus,
        ]);

        Log::info("Subscription [{$asaasSubscriptionId}] updated to {$gatewayStatus}/{$localStatus}.");
    }
}
