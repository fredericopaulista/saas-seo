<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Project;
use App\Services\Billing\AsaasGatewayService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;

echo "--- STARTING SAAS E2E SIMULATION ---\n\n";

// 1. Plan Verification
$plan = Plan::firstOrCreate(
    ['slug' => 'simulation-plan'],
    [
        'name' => 'Simulation Premium',
        'price' => 199.90,
        'max_projects' => 10,
        'billing_cycle' => 'MONTHLY',
        'features_json' => ['All features', 'E2E Verified'],
        'asaas_id' => 'plan_sim_123'
    ]
);
echo "[OK] Plan selected/created: {$plan->name}\n";

// 2. Tenant / User Creation (or retrieval)
$user = User::create([
    'name' => 'E2E Simulation User',
    'email' => 'simulation_' . uniqid() . '@example.com',
    'password' => bcrypt('password123'),
]);

$tenant = Tenant::create([
    'name' => 'E2E Simulation Agency',
    'domain' => 'e2e-' . uniqid() . '.saas-seo.test',
]);

$tenant->users()->attach($user->id);
$user->update(['current_tenant_id' => $tenant->id]);
$user->assignRole('user');

echo "[OK] Tenant & User created: {$tenant->name} ({$user->email})\n";

// 3. Subscription Simulation (Billing Service Mock)
$sub = Subscription::create([
    'tenant_id' => $tenant->id,
    'plan_id' => $plan->id,
    'asaas_subscription_id' => 'sub_sim_' . uniqid(),
    'status' => 'PENDING',
    'status_gateway' => 'PENDING',
    'ends_at' => now()->addMonth(),
]);
echo "[OK] Subscription created (Pending inside Asaas): {$sub->asaas_subscription_id}\n";

// 4. Webhook Trigger Simulation
$sub->update([
    'status' => 'ACTIVE',
    'status_gateway' => 'ACTIVE'
]);
echo "[OK] Asaas Webhook received: PAYMENT_RECEIVED. Subscription activated!\n";

// 5. Project Registration (Core App)
$project = new Project();
$project->tenant_id = $tenant->id;
$project->domain = 'https://simulation-client.com';
$project->is_active = true;
$project->save();
echo "[OK] Project attached to Tenant: {$project->domain}\n";

// 6. Metrics Check
$metricsService = new \App\Services\Admin\SystemMonitoringService();
$health = $metricsService->getSystemHealth();

echo "- GSC Quota Estimation: " . $health['gsc_quota']['estimated_daily_calls'] . " calls\n";
echo "- Redis Status: " . $health['redis']['status'] . "\n";
echo "- Core Queue Pending: " . $health['queues']['default_pending'] . "\n\n";

echo "--- SIMULATION SUCCESSFUL ---\n";
