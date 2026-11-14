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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('destination_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->enum('type', ['income', 'expense', 'transfer'])->default('expense');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('INR');
            $table->decimal('fee', 15, 2)->default(0);
            $table->date('transaction_date');
            $table->string('description');
            $table->string('payee_payer')->nullable();
            $table->string('payment_method')->default('bank_transfer'); // cash, card, bank_transfer, upi, check, other
            $table->string('reference_number')->nullable();
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_interval', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->nullable();
            $table->enum('status', ['completed', 'pending', 'cancelled'])->default('completed');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'transaction_date']);
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

