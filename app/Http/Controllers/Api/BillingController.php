<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Billing\AsaasGatewayService;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    protected AsaasGatewayService $asaasService;

    public function __construct(AsaasGatewayService $asaasService)
    {
        $this->asaasService = $asaasService;
    }

    /**
     * Fetch available plans
     */
    public function getPlans()
    {
        return response()->json(Plan::all());
    }

    /**
     * Process checkout/subscription for a specific Plan
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billingType' => 'required|in:CREDIT_CARD,PIX,BOLETO'
        ]);

        $user = auth()->user();
        $tenant = $user->tenant; // Assuming user->tenant mapping is established

        // Prevent double subscribing active plans
        $activeSubscription = Subscription::where('tenant_id', $tenant->id)
            ->where('status_gateway', 'ACTIVE')
            ->first();

        if ($activeSubscription) {
            return response()->json(['message' => 'Você já possui uma assinatura ativa.'], 400);
        }

        $plan = Plan::find($request->plan_id);

        /**
         * Real-world scenario: We'd check if customer exists in DB first, 
         * then create if not, but for MVP we send directly.
         */
        $remoteCustomer = $this->asaasService->createCustomer(
            $user->name,
            $user->email,
            // Assuming tenant or user has CPF/CNPJ. We fallback to dummy for MVP flow test.
            $tenant->cpf_cnpj ?? '00000000000' 
        );

        if (!$remoteCustomer) {
            return response()->json(['message' => 'Falha ao sincronizar cliente com o Gateway de Pagamento'], 500);
        }

        $remoteSubscription = $this->asaasService->createSubscription(
            $remoteCustomer['id'],
            $request->billingType,
            $plan->price
        );

        if (!$remoteSubscription) {
            return response()->json(['message' => 'Falha ao gerar cobrança no Gateway de Pagamento'], 500);
        }

        // Create local record pending payment notification
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'asaas_subscription_id' => $remoteSubscription['id'],
            'status' => 'pending',
            'status_gateway' => 'PENDING',
        ]);

        return response()->json([
            'message' => 'Assinatura gerada com sucesso. Aguardando pagamento.',
            'subscription' => $subscription,
            // Asaas usually returns invoiceUrl for BOLETO/PIX, or credit card forms.
            'paymentUrl' => $remoteSubscription['invoiceUrl'] ?? null 
        ]);
    }
}
