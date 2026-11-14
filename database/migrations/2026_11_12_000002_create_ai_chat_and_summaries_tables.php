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
        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant'])->default('user');
            $table->text('message');
            $table->json('data_payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('monthly_financial_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('month_year', 7); // e.g. '2026-11'
            $table->decimal('total_income', 15, 2)->default(0);
            $table->decimal('total_expenses', 15, 2)->default(0);
            $table->decimal('net_savings', 15, 2)->default(0);
            $table->decimal('savings_rate', 5, 2)->default(0);
            $table->unsignedInteger('health_score')->default(80); // 0-100
            $table->text('executive_summary');
            $table->json('spending_highlights')->nullable();
            $table->json('top_categories')->nullable();
            $table->json('recommendations')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'month_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_financial_summaries');
        Schema::dropIfExists('ai_chat_messages');
    }
};

