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
        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // spending_anomaly, budget_warning, savings_opportunity, cashflow_forecast, business_advice
            $table->string('title');
            $table->text('summary');
            $table->longText('detailed_analysis')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->text('action_suggested')->nullable();
            $table->json('metrics_snapshot')->nullable();
            $table->boolean('is_dismissed')->default(false);
            $table->boolean('action_taken')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
    }
};
