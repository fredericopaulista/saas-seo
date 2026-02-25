<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('url_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('coverage_status')->nullable();
            $table->string('index_status')->nullable();
            $table->string('canonical_declared', 2048)->nullable();
            $table->string('canonical_google', 2048)->nullable();
            $table->boolean('mobile_usable')->nullable();
            $table->timestamp('last_crawled')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('url_statuses');
    }
};
