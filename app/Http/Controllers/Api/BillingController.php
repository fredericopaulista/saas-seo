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
        return response()->json(Plan::where('slug', '!=', 'super-admin-unlimited')->get());
    }

    /**
     * Get the logged in tenant's active subscription
     */
    public function mySubscription()
    {
        $tenantId = auth()->user()->current_tenant_id;
        
        // Fallback robust resolution if session tokens are misaligned
        if (!$tenantId) {
            $firstTenant = auth()->user()->tenants()->first();
            if ($firstTenant) {
                $tenantId = $firstTenant->id;
                auth()->user()->update(['current_tenant_id' => $tenantId]);
            } else {
                return response()->json(['subscription' => null]);
            }
        }

        $subscription = Subscription::with('plan')
            ->where('tenant_id', $tenantId)
            ->whereIn('status_gateway', ['ACTIVE', 'PENDING'])
            ->latest()
            ->first();

        return response()->json(['subscription' => $subscription]);
    }

    /**
     * Process checkout/subscription for a specific Plan
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billingType' => 'required|in:CREDIT_CARD,PIX,BOLETO',
            'cpfCnpj' => 'required|string|min:11'
        ]);

        $user = auth()->user();
        $tenantId = $user->current_tenant_id;
        if (!$tenantId && $user->tenants()->exists()) {
            $tenantId = $user->tenants()->first()->id;
            $user->update(['current_tenant_id' => $tenantId]);
        }

        // Prevent double subscribing active plans if trying to subscribe to the same plan
        $activeSubscription = Subscription::where('tenant_id', $tenantId)
            ->whereIn('status_gateway', ['ACTIVE', 'PENDING'])
            ->first();

        // If they have an active plan and they chose the same plan:
        if ($activeSubscription && $activeSubscription->plan_id == $request->plan_id) {
            return response()->json(['message' => 'Você já possui este plano ativo.'], 400);
        }

        // Real world note: If they have an active sub to a different plan, we should cancel the old one first or update it.
        // For this immediate MVP flow, if an active one exists, we cancel the old one.
        if ($activeSubscription) {
            if ($activeSubscription->asaas_subscription_id) {
                // Ignore cancel failure internally
                $this->asaasService->cancelSubscription($activeSubscription->asaas_subscription_id);
            }
            $activeSubscription->update(['status_gateway' => 'CANCELED', 'status' => 'canceled']);
        }

        $plan = Plan::find($request->plan_id);

        /**
         * Real-world scenario: We'd check if customer exists in DB first, 
         * then create if not, but for MVP we send directly.
         */
        $remoteCustomer = $this->asaasService->createCustomer(
            $user->name,
            $user->email,
            // Dynamically passed from frontend checkout modal
            $request->cpfCnpj 
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
            'tenant_id' => $tenantId,
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

    /**
     * Cancels the active subscription for the current tenant.
     */
    public function cancelSubscription()
    {
        $tenantId = auth()->user()->current_tenant_id;
        if (!$tenantId) {
            $firstTenant = auth()->user()->tenants()->first();
            if ($firstTenant) {
                $tenantId = $firstTenant->id;
                auth()->user()->update(['current_tenant_id' => $tenantId]);
            }
        }
        
        $activeSubscription = Subscription::where('tenant_id', $tenantId)
            ->whereIn('status_gateway', ['ACTIVE', 'PENDING'])
            ->first();
            
        if (!$activeSubscription) {
            return response()->json(['message' => 'Nenhuma assinatura ativa encontrada.'], 400);
        }

        if ($activeSubscription->asaas_subscription_id) {
            $canceled = $this->asaasService->cancelSubscription($activeSubscription->asaas_subscription_id);
            if (!$canceled) {
                return response()->json(['message' => 'Falha ao processar cancelamento. Contate o suporte.'], 500);
            }
        }

        $activeSubscription->update([
            'status' => 'canceled',
            'status_gateway' => 'CANCELED',
        ]);

        return response()->json(['message' => 'Assinatura cancelada com sucesso.']);
    }
}
