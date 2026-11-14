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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('account_name', 150)->nullable();
            $table->string('name')->nullable();
            $table->string('account_type', 50)->nullable();
            $table->string('type')->default('checking'); // checking, savings, credit_card, cash, business_checking, investment
            $table->string('account_number_last4', 4)->nullable();
            $table->decimal('opening_balance', 15, 2)->nullable();
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('initial_balance', 15, 2)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->string('color', 20)->default('#2563eb');
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};

