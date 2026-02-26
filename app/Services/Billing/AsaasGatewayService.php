<?php

namespace App\Services\Billing;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use App\Models\Setting;

class AsaasGatewayService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        // Try to load via database Settings
        $apiKeySetting = Setting::where('key', 'ASAAS_API_KEY')->first();
        $envSetting = Setting::where('key', 'ASAAS_ENVIRONMENT')->first();
        
        $dbApiKey = '';
        if ($apiKeySetting && !empty($apiKeySetting->value)) {
            try {
                $dbApiKey = Crypt::decryptString($apiKeySetting->value);
            } catch (\Exception $e) {
                // Keep it empty on fail
            }
        }
        
        $env = $envSetting ? $envSetting->value : 'sandbox';
        
        // Prioritize Database, then .env fallback, then defaults
        $this->apiKey = $dbApiKey ?: config('services.asaas.key', '');
        
        if ($env === 'production') {
            $this->baseUrl = 'https://api.asaas.com/v3';
        } else {
            $this->baseUrl = 'https://sandbox.asaas.com/api/v3';
        }
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
