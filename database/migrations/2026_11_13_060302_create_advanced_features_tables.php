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
        // 1. Currency Exchange Rates for multi-currency conversion
        Schema::create('currency_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('rate', 18, 6);
            $table->string('source', 50)->default('central_bank');
            $table->timestamps();

            $table->unique(['from_currency', 'to_currency']);
            $table->index(['from_currency', 'to_currency']);
        });

        // 2. Add AI cost tracking columns to ai_messages
        if (Schema::hasTable('ai_messages')) {
            Schema::table('ai_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('ai_messages', 'tokens_prompt')) {
                    $table->unsignedInteger('tokens_prompt')->nullable()->after('tokens_used');
                }
                if (!Schema::hasColumn('ai_messages', 'tokens_completion')) {
                    $table->unsignedInteger('tokens_completion')->nullable()->after('tokens_prompt');
                }
                if (!Schema::hasColumn('ai_messages', 'cost_usd')) {
                    $table->decimal('cost_usd', 10, 6)->default(0)->after('tokens_completion');
                }
                if (!Schema::hasColumn('ai_messages', 'cost_inr')) {
                    $table->decimal('cost_inr', 10, 4)->default(0)->after('cost_usd');
                }
            });
        }

        // 3. Add subscription & usage limit tracking to users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'subscription_plan')) {
                $table->string('subscription_plan', 30)->default('free')->after('role'); // free, pro, enterprise
            }
            if (!Schema::hasColumn('users', 'subscription_status')) {
                $table->string('subscription_status', 30)->default('active')->after('subscription_plan'); // active, trialing, expired, canceled
            }
            if (!Schema::hasColumn('users', 'subscription_expires_at')) {
                $table->timestamp('subscription_expires_at')->nullable()->after('subscription_status');
            }
            if (!Schema::hasColumn('users', 'daily_ai_queries_count')) {
                $table->unsignedInteger('daily_ai_queries_count')->default(0)->after('subscription_expires_at');
            }
            if (!Schema::hasColumn('users', 'last_ai_query_date')) {
                $table->date('last_ai_query_date')->nullable()->after('daily_ai_queries_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_exchange_rates');

        if (Schema::hasTable('ai_messages')) {
            Schema::table('ai_messages', function (Blueprint $table) {
                $table->dropColumn(['tokens_prompt', 'tokens_completion', 'cost_usd', 'cost_inr']);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_plan',
                'subscription_status',
                'subscription_expires_at',
                'daily_ai_queries_count',
                'last_ai_query_date',
            ]);
        });
    }
};
