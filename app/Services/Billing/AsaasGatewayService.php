<?php

namespace App\Services\Billing;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
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
            $val = $apiKeySetting->value;
            // Check if it's likely a Laravel encrypted string (starts with eyJ)
            if (str_starts_with($val, 'eyJ')) {
                try {
                    $dbApiKey = Crypt::decryptString($val);
                } catch (\Exception $e) {
                    $dbApiKey = $val; // Fallback to plain if it's not actually encrypted
                }
            } else {
                $dbApiKey = $val;
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
     * Get the current API Key
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Retrieve the webhook token from the database (decrypted), 
     * falling back to the .env value.
     * Static so the webhook controller can call it without full DI.
     */
    public static function getWebhookToken(): string
    {
        $setting = Setting::where('key', 'ASAAS_WEBHOOK_TOKEN')->first();

        if ($setting && !empty($setting->value)) {
            $val = $setting->value;
            // Check if it's likely a Laravel encrypted string (starts with eyJ)
            if (str_starts_with($val, 'eyJ')) {
                try {
                    return Crypt::decryptString($val);
                } catch (\Exception $e) {
                    return $val; // Fallback to plain
                }
            }
            return $val;
        }

        return config('services.asaas.webhook_token', '');
    }

    /**
     * Create or retrieve a customer in Asaas
     */
    public function createCustomer(string $name, string $email, ?string $cpfCnpj, ?string $phone = null): ?array
    {
        $response = Http::withHeaders([
            'access_token' => $this->apiKey,
        ])->post("{$this->baseUrl}/customers", [
            'name' => $name,
            'email' => $email,
            'cpfCnpj' => $cpfCnpj,
            'mobilePhone' => $phone,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Asaas Create Customer Error:', [
            'status' => $response->status(),
            'body' => $response->json()
        ]);

        return null;
    }

    /**
     * Creates a subscription for an existing Customer
     */
    public function createSubscription(
        string $customerId, 
        string $billingType, 
        float $value, 
        string $cycle = 'MONTHLY',
        ?array $creditCard = null,
        ?array $creditCardHolderInfo = null
    ): ?array {
        $payload = [
            'customer' => $customerId,
            'billingType' => $billingType, // PIX, CREDIT_CARD, BOLETO
            'value' => $value,
            'nextDueDate' => now()->addDays(1)->format('Y-m-d'),
            'cycle' => $cycle,
            'description' => 'SaaS SEO Premium Subscription'
        ];

        if ($billingType === 'CREDIT_CARD' && $creditCard) {
            $payload['creditCard'] = $creditCard;
            $payload['creditCardHolderInfo'] = $creditCardHolderInfo;
        }

        $response = Http::withHeaders([
            'access_token' => $this->apiKey,
        ])->post("{$this->baseUrl}/subscriptions", $payload);

        if ($response->successful()) {
            return $response->json();
        }
        
        Log::error('Asaas Subscription Error:', [
            'status' => $response->status(),
            'body' => $response->json()
        ]);

        return null;
    }

    /**
     * Get PIX QR Code for a specific payment
     */
    public function getPixQrCode(string $paymentId): ?array
    {
        $response = Http::withHeaders([
            'access_token' => $this->apiKey,
        ])->get("{$this->baseUrl}/payments/{$paymentId}/pixQrCode");

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

    /**
     * Refunds a specific payment in Asaas
     */
    public function refundPayment(string $paymentId, ?float $value = null, ?string $description = null): ?array
    {
        $payload = [];
        if ($value) $payload['value'] = $value;
        if ($description) $payload['description'] = $description;

        $response = Http::withHeaders([
            'access_token' => $this->apiKey,
        ])->post("{$this->baseUrl}/payments/{$paymentId}/refund", $payload);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Asaas Refund Error:', [
            'status' => $response->status(),
            'body' => $response->json(),
            'payment_id' => $paymentId
        ]);

        return null;
    }

    /**
     * Fetches payments generated by a subscription (to get invoiceUrl/pixQrCode)
     * Asaas creates a payment automatically when a subscription is created.
     * We need to query it to get the payment link.
     */
    public function getSubscriptionPayments(string $subscriptionId): ?array
    {
        $response = Http::withHeaders([
            'access_token' => $this->apiKey,
        ])->get("{$this->baseUrl}/payments", [
            'subscription' => $subscriptionId,
            'limit' => 1,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['data'][0] ?? null;
        }

        return null;
    }

    /**
     * Register (or update) this application's webhook URL in Asaas.
     * Call this once after deployment via an admin action.
     *
     * @param  string $url   The public URL that Asaas will POST events to
     * @param  string $token A secret token you define; Asaas sends it as `asaas-access-token`
     * @return array|null    The created/updated webhook object, or null on failure
     */
    public function registerWebhook(string $url, string $token): ?array
    {
        $response = Http::withHeaders([
            'access_token' => $this->apiKey,
        ])->post("{$this->baseUrl}/webhook", [
            'url'     => $url,
            'email'   => config('mail.from.address', 'webhook@example.com'),
            'enabled' => true,
            'interrupted' => false,
            'authToken' => $token,   // Asaas uses this as the `asaas-access-token` header
            'events'  => [
                'PAYMENT_RECEIVED',
                'PAYMENT_CONFIRMED',
                'PAYMENT_OVERDUE',
                'PAYMENT_DELETED',
                'PAYMENT_REFUNDED',
                'PAYMENT_REFUND_CONFIRMED',
                'PAYMENT_CHARGEBACK_REQUESTED',
                'PAYMENT_CHARGEBACK_DISPUTE',
                'SUBSCRIPTION_DELETED',
            ],
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * List all webhooks registered in Asaas.
     */
    public function listWebhooks(): ?array
    {
        $response = Http::withHeaders([
            'access_token' => $this->apiKey,
        ])->get("{$this->baseUrl}/webhook");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }
}
