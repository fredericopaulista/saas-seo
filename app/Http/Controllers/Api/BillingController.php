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
        Log::info('Subscription Request Data:', $request->all());

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
            'billingType' => 'required|in:CREDIT_CARD,PIX,BOLETO',
            'cpfCnpj' => 'required|string|min:11',
            'phone' => 'required|string|min:10',
            'name' => 'required|string|min:3',
            'creditCard' => 'nullable|array',
            'creditCardHolderInfo' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            Log::warning('Subscription Validation Failed:', [
                'errors' => $validator->errors()->toArray(),
                'data' => $request->all()
            ]);
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

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

        // Check if API Key is configured
        if (empty(app(\App\Services\Billing\AsaasGatewayService::class)->getApiKey())) {
            return response()->json(['message' => 'O sistema de pagamentos não está configurado. O administrador precisa inserir a Asaas API Key no painel.'], 500);
        }

        $phone = $request->phone ?: ($request->creditCardHolderInfo['phone'] ?? null);

        $remoteCustomer = $this->asaasService->createCustomer(
            $request->name ?? $user->name,
            $user->email,
            $request->cpfCnpj,
            $phone
        );

        if (!$remoteCustomer) {
            Log::error('Customer creation failed. Input data:', [
                'name' => $request->name,
                'cpfCnpj' => $request->cpfCnpj,
                'phone' => $phone
            ]);
            return response()->json(['message' => 'Falha ao sincronizar cliente com o Gateway. Verifique se o CPF e Telefone estão corretos.'], 500);
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
