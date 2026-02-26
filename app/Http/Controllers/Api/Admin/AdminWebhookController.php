<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Billing\AsaasGatewayService;
use Illuminate\Http\Request;

class AdminWebhookController extends Controller
{
    public function __construct(protected AsaasGatewayService $asaas) {}

    /**
     * Register the webhook URL in Asaas.
     * Call this once after every deployment from the Admin panel.
     *
     * POST /api/admin/billing/register-webhook
     */
    public function register(Request $request)
    {
        $webhookUrl   = rtrim(config('app.url'), '/') . '/api/webhooks/asaas';
        $webhookToken = AsaasGatewayService::getWebhookToken(); // reads from DB

        if (empty($webhookToken)) {
            return response()->json([
                'error' => 'ASAAS_WEBHOOK_TOKEN não está configurado no painel Admin → Configurações.',
            ], 422);
        }

        $result = $this->asaas->registerWebhook($webhookUrl, $webhookToken);

        if (!$result) {
            return response()->json([
                'error'       => 'Falha ao registrar webhook no Asaas.',
                'webhook_url' => $webhookUrl,
            ], 500);
        }

        return response()->json([
            'message'     => 'Webhook registrado com sucesso no Asaas.',
            'webhook_url' => $webhookUrl,
            'asaas'       => $result,
        ]);
    }

    /**
     * List webhooks registered in Asaas.
     *
     * GET /api/admin/billing/webhooks
     */
    public function index()
    {
        $result = $this->asaas->listWebhooks();

        return response()->json($result ?? ['data' => []]);
    }

    /**
     * Return the webhook URL that should be registered in Asaas.
     *
     * GET /api/admin/billing/webhook-url
     */
    public function webhookUrl()
    {
        return response()->json([
            'webhook_url' => rtrim(config('app.url'), '/') . '/api/webhooks/asaas',
        ]);
    }
}
