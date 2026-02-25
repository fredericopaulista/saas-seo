<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('page', 2048)->nullable();
            $table->string('query')->nullable();
            $table->integer('clicks')->default(0);
            $table->integer('impressions')->default(0);
            $table->float('ctr')->default(0);
            $table->float('position')->default(0);
            $table->string('device')->nullable();
            $table->string('country')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_data');
    }
};
