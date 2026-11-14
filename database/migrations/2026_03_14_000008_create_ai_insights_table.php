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
            $table->string('insight_type', 100)->nullable();
            $table->string('type')->nullable(); // spending_anomaly, budget_warning, savings_opportunity, cashflow_forecast, business_advice
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('summary')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->json('data_snapshot')->nullable();
            $table->json('metrics_snapshot')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->longText('detailed_analysis')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->text('action_suggested')->nullable();
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

