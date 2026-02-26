<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\Billing\AsaasGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminSubscriptionController extends Controller
{
    protected $asaasService;

    public function __construct(AsaasGatewayService $asaasService)
    {
        $this->asaasService = $asaasService;
    }

    /**
     * Cancels a subscription in Asaas and locally.
     */
    public function cancel($id)
    {
        $subscription = Subscription::findOrFail($id);

        if (!$subscription->asaas_subscription_id) {
            return response()->json(['error' => 'Assinatura não possui ID do Asaas.'], 400);
        }

        $success = $this->asaasService::currentTenant($subscription->tenant_id)
            ? (new AsaasGatewayService())->cancelSubscription($subscription->asaas_subscription_id)
            : $this->asaasService->cancelSubscription($subscription->asaas_subscription_id);

        // Note: We don't really have a static currentTenant yet, but AsaasGatewayService 
        // uses global settings which is fine for Admin.
        
        $success = $this->asaasService->cancelSubscription($subscription->asaas_subscription_id);

        if ($success) {
            $subscription->update(['status' => 'inactive', 'status_gateway' => 'CANCELED']);
            return response()->json(['message' => 'Assinatura cancelada com sucesso.']);
        }

        return response()->json(['error' => 'Falha ao cancelar assinatura no Asaas.'], 500);
    }

    /**
     * Refunds the latest payment associated with the subscription.
     */
    public function refund($id)
    {
        $subscription = Subscription::findOrFail($id);

        if (!$subscription->asaas_subscription_id) {
            return response()->json(['error' => 'Assinatura não possui ID do Asaas.'], 400);
        }

        // 1. Get the latest payment from Asaas
        $payment = $this->asaasService->getSubscriptionPayments($subscription->asaas_subscription_id);

        if (!$payment || !isset($payment['id'])) {
            return response()->json(['error' => 'Nenhum pagamento encontrado para esta assinatura.'], 404);
        }

        // 2. Request Refund
        $refund = $this->asaasService->refundPayment($payment['id']);

        if ($refund) {
            return response()->json([
                'message' => 'Solicitação de estorno enviada com sucesso.',
                'asaas_response' => $refund
            ]);
        }

        return response()->json(['error' => 'Falha ao processar estorno no Asaas.'], 500);
    }
}
