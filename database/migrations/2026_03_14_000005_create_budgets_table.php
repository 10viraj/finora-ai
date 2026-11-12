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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('amount', 15, 2);
            $table->enum('period', ['monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->string('month_year', 7)->nullable(); // e.g., '2026-11'
            $table->unsignedInteger('alert_threshold')->default(80); // percentage: 80%
            $table->boolean('notify_on_exceed')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'month_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
