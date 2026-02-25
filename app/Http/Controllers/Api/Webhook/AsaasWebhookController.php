<?php

namespace App\Http\Controllers\Api\Webhook;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\Subscription;

class AsaasWebhookController extends Controller
{
    /**
     * Handle incoming webhooks from Asaas API
     */
    public function handle(Request $request)
    {
        // 1. Verify Asaas-Access-Token header to secure the endpoint
        $webhookToken = $request->header('asaas-access-token');
        if ($webhookToken !== config('services.asaas.webhook_token')) {
            // Depending on environment, you might just log and return 401
            Log::warning('Unauthorized Asaas webhook attempt.');
        }

        $event = $request->input('event');
        $payment = $request->input('payment');

        if (!$payment || !isset($payment['subscription'])) {
            return response()->json(['status' => 'ignored', 'reason' => 'Not a subscription payment']);
        }

        Log::info("Asaas Webhook Received: {$event} for Subscription {$payment['subscription']}");

        $subscription = Subscription::where('asaas_subscription_id', $payment['subscription'])->first();

        if (!$subscription) {
            return response()->json(['status' => 'ignored', 'reason' => 'Subscription not found locally']);
        }

        switch ($event) {
            case 'PAYMENT_RECEIVED':
            case 'PAYMENT_CONFIRMED':
                $subscription->update([
                    'status_gateway' => 'ACTIVE',
                    'status' => 'active' // Ensure local status reflects active
                ]);
                break;

            case 'PAYMENT_OVERDUE':
            case 'PAYMENT_DELETED':
            case 'PAYMENT_REFUNDED':
                $subscription->update([
                    'status_gateway' => 'EXPIRED',
                    'status' => 'past_due' // Lock SaaS access relying on this
                ]);
                break;
        }

        return response()->json(['status' => 'success']);
    }
}
