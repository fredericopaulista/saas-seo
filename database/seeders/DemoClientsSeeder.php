<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoClientsSeeder extends Seeder
{
    public function run(): void
    {
        $plans = Plan::all();
        if ($plans->count() === 0) {
            $this->command->error('Run PlanSeeder first!');
            return;
        }

        $demoUsers = [
            ['name' => 'John Doe SEO', 'email' => 'john.agency@example.com'],
            ['name' => 'Maria Consultant', 'email' => 'maria@seospecialist.net'],
            ['name' => 'Carlos Ecomm', 'email' => 'carlos@lojadetech.com'],
            ['name' => 'Ana Startup', 'email' => 'ana@saasgrowth.com']
        ];

        foreach ($demoUsers as $idx => $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                ['name' => $u['name'], 'password' => bcrypt('password')]
            );

            $tenant = Tenant::firstOrCreate(
                ['domain' => 'tenant-'.($idx+1).'.localhost'],
                ['name' => $u['name'].' Workspace']
            );

            if (!$user->tenants->contains($tenant->id)) {
                $user->tenants()->attach($tenant->id);
            }

            $plan = $plans->random();
            
            Subscription::firstOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'status_gateway' => 'ACTIVE',
                    'asaas_subscription_id' => 'sub_mock_'.Str::random(10)
                ]
            );
        }
        
        $this->command->info('Mock clients and subscriptions created successfully!');
    }
}
