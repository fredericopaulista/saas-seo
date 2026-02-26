<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = [
            [
                'key' => 'ASAAS_ENVIRONMENT',
                'value' => 'sandbox',
                'group' => 'asaas_gateway',
                'type' => 'string',
            ],
            [
                'key' => 'ASAAS_API_KEY',
                'value' => null, // To be configured in Admin Panel
                'group' => 'asaas_gateway',
                'type' => 'string',
            ],
            [
                'key' => 'ASAAS_WEBHOOK_TOKEN',
                'value' => Crypt::encryptString('whsec_-tMUqOMY8WpH98ZBjHqpDpJJ6qUfAT63kViycJD0Zh0'),
                'group' => 'asaas_gateway',
                'type' => 'string',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                    'type' => $setting['type'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', ['ASAAS_ENVIRONMENT', 'ASAAS_API_KEY', 'ASAAS_WEBHOOK_TOKEN'])
            ->delete();
    }
};
