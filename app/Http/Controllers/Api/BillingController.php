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
            'cpfCnpj' => 'required|string|min:11',
            'phone' => 'required|string|min:10',
            // Optional credit card validation
            'creditCard' => 'nullable|array',
            'creditCardHolderInfo' => 'nullable|array',
        ]);

        $user = auth()->user();
        $tenantId = $user->current_tenant_id;
        if (!$tenantId && $user->tenants()->exists()) {
            $tenantId = $user->tenants()->first()->id;
            $user->update(['current_tenant_id' => $tenantId]);
        }

        // Prevent double subscribing active plans
        $activeSubscription = Subscription::where('tenant_id', $tenantId)
            ->whereIn('status_gateway', ['ACTIVE', 'PENDING'])
            ->first();

        if ($activeSubscription && $activeSubscription->plan_id == $request->plan_id) {
            return response()->json(['message' => 'Você já possui este plano ativo.'], 400);
        }

        // Cancel old subscription if exists
        if ($activeSubscription) {
            if ($activeSubscription->asaas_subscription_id) {
                $this->asaasService->cancelSubscription($activeSubscription->asaas_subscription_id);
            }
            $activeSubscription->update(['status_gateway' => 'CANCELED', 'status' => 'canceled']);
        }

        $plan = Plan::find($request->plan_id);

        if ((float) $plan->price <= 0) {
            $subscription = Subscription::create([
                'tenant_id' => $tenantId,
                'plan_id' => $plan->id,
                'asaas_subscription_id' => null,
                'status' => 'active',
                'status_gateway' => 'ACTIVE',
            ]);
            return response()->json([
                'message' => 'Plano ativado com sucesso.',
                'subscription' => $subscription,
                'paymentUrl' => null
            ]);
        }

        $remoteCustomer = $this->asaasService->createCustomer(
            $request->name ?? $user->name, // use name from request if provided
            $user->email,
            $request->cpfCnpj,
            $request->phone
        );

        if (!$remoteCustomer) {
            return response()->json(['message' => 'Falha ao sincronizar cliente com o Gateway de Pagamento'], 500);
        }

        $remoteSubscription = $this->asaasService->createSubscription(
            $remoteCustomer['id'],
            $request->billingType,
            (float) $plan->price,
            'MONTHLY',
            $request->creditCard,
            $request->creditCardHolderInfo
        );

        if (!$remoteSubscription) {
            return response()->json(['message' => 'Falha ao gerar cobrança no Gateway de Pagamento. Verifique os dados do cartão.'], 500);
        }

        // Create local record
        $subscription = Subscription::create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->id,
            'asaas_subscription_id' => $remoteSubscription['id'],
            'status' => 'pending',
            'status_gateway' => 'PENDING',
        ]);

        $payment = null;
        $paymentUrl = null;
        $pixData = null;

        // Small delay for Asaas background processing
        sleep(1);
        $payment = $this->asaasService->getSubscriptionPayments($remoteSubscription['id']);

        if ($payment) {
            $paymentUrl = $payment['invoiceUrl'] ?? $payment['bankSlipUrl'] ?? null;

            if ($request->billingType === 'PIX' && isset($payment['id'])) {
                // Fetch dedicated PIX QR Code data
                $qrCodeRes = $this->asaasService->getPixQrCode($payment['id']);
                if ($qrCodeRes) {
                    $pixData = [
                        'pixQrCode'    => $qrCodeRes['encodedImage'] ?? null,
                        'pixCopiaECola'=> $qrCodeRes['payload']      ?? null,
                        'invoiceUrl'   => $paymentUrl,
                    ];
                }
            }
        }

        return response()->json([
            'message'     => 'Assinatura gerada com sucesso. Aguardando pagamento.',
            'subscription' => $subscription,
            'paymentUrl'  => $paymentUrl,
            'pixData'     => $pixData,
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
