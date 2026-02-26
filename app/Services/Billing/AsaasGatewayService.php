<?php

namespace App\Services\Billing;

use Illuminate\Support\Facades\Http;

class AsaasGatewayService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        // Using Sandbox defaults if env not provided
        $this->apiKey = config('services.asaas.key', '');
        $this->baseUrl = config('services.asaas.url', 'https://sandbox.asaas.com/api/v3');
    }

    /**
     * Create or retrieve a customer in Asaas
     */
    public function createCustomer(string $name, string $email, string $cpfCnpj): ?array
    {
        $response = Http::withHeaders([
            'access_token' => $this->apiKey,
        ])->post("{$this->baseUrl}/customers", [
            'name' => $name,
            'email' => $email,
            'cpfCnpj' => $cpfCnpj,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Creates a subscription for an existing Customer
     */
    public function createSubscription(string $customerId, string $billingType, float $value, string $cycle = 'MONTHLY'): ?array
    {
        $response = Http::withHeaders([
            'access_token' => $this->apiKey,
        ])->post("{$this->baseUrl}/subscriptions", [
            'customer' => $customerId,
            'billingType' => $billingType, // PIX, CREDIT_CARD, BOLETO
            'value' => $value,
            'nextDueDate' => now()->addDays(1)->format('Y-m-d'),
            'cycle' => $cycle,
            'description' => 'SaaS SEO Premium Subscription'
        ]);

        if ($response->successful()) {
            return $response->json();
        }
        
        return null;
    }

    /**
     * Cancels an active subscription in Asaas
     */
    public function cancelSubscription(string $subscriptionId): bool
    {
        $response = Http::withHeaders([
            'access_token' => $this->apiKey,
        ])->delete("{$this->baseUrl}/subscriptions/{$subscriptionId}");

        return $response->successful();
    }
}
