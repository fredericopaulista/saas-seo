<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('asaas_subscription_id')->nullable()->after('plan_id');
            $table->string('status_gateway')->nullable()->after('status')->comment('ACTIVE, EXPIRED, CANCELED');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['asaas_subscription_id', 'status_gateway']);
        });
    }
};
