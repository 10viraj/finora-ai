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
        // 1. Payment Methods (Cash, UPI, card, bank)
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->index();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'code']);
        });

        // Link transactions table with payment_methods
        if (Schema::hasTable('transactions') && !Schema::hasColumn('transactions', 'payment_method_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreignId('payment_method_id')->nullable()->after('category_id')->constrained('payment_methods')->nullOnDelete();
            });
        }

        // 2. Budget Categories (Category budget allocations)
        Schema::create('budget_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->decimal('allocated_amount', 15, 2);
            $table->decimal('spending_limit', 15, 2)->nullable();
            $table->unsignedInteger('alert_threshold')->default(80);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['budget_id', 'category_id']);
        });

        // 3. Recurring Transactions (Scheduled recurring expenses)
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('destination_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->enum('type', ['income', 'expense', 'transfer'])->default('expense');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('INR');
            $table->decimal('fee', 15, 2)->default(0);
            $table->string('description');
            $table->string('payee_payer')->nullable();
            $table->string('payment_method')->default('bank_transfer');
            $table->enum('recurrence_interval', ['daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_run_date');
            $table->date('last_run_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_record')->default(true);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'next_run_date', 'is_active']);
        });

        // 4. Attachments (Receipts and bills)
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('attachable_type');
            $table->unsignedBigInteger('attachable_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id']);
            $table->index('user_id');
        });

        // 5. Notifications (Budget and transaction alerts)
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type')->default('general'); // budget_alert, spending_anomaly, limit_breached, payment_due
            $table->string('title')->default('Alert');
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });

        // 6. AI Conversations (AI chat sessions)
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('context_type')->default('general_finance');
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // 7. AI Messages (AI conversation messages)
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system'])->default('user');
            $table->longText('content')->nullable();
            $table->text('message')->nullable();
            $table->unsignedInteger('token_usage')->nullable();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->json('data_payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('conversation_id');
        });

        // 8. AI Recommendations (AI saving recommendations)
        Schema::create('ai_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insight_id')->nullable()->constrained('ai_insights')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('title');
            $table->string('recommendation_type'); // subscription_reduction, budget_cap, reserve_sweep, vendor_discount
            $table->decimal('suggested_amount', 15, 2)->nullable();
            $table->decimal('potential_savings', 15, 2)->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['pending', 'applied', 'dismissed'])->default('pending');
            $table->json('action_payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // 9. Audit Logs (Important system activity)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('action'); // e.g. login, transaction.create, transaction.delete, budget.update
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('ai_recommendations');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('recurring_transactions');
        Schema::dropIfExists('budget_categories');

        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'payment_method_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('payment_method_id');
            });
        }

        Schema::dropIfExists('payment_methods');
    }
};

