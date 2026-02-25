<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Plan::firstOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'price' => 29.00,
                'max_projects' => 1,
                'features_json' => ['3 Months Retention', 'Basic Insights', '1 GSC Property']
            ]
        );

        \App\Models\Plan::firstOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'price' => 99.00,
                'max_projects' => 5,
                'features_json' => ['16 Months Retention', 'AI Action Plan', 'PDF Reports', 'Up to 5 GSC Properties']
            ]
        );

        \App\Models\Plan::firstOrCreate(
            ['slug' => 'agency'],
            [
                'name' => 'Agency',
                'price' => 299.00,
                'max_projects' => 50,
                'features_json' => ['Unlimited Historical Data', 'Full White Label', 'Unlimited Seats', 'Up to 50 GSC Properties']
            ]
        );
    }
}
