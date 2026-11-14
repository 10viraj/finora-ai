<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;

class SubscriptionService
{
    /**
     * Tier definitions, feature flags, and quotas.
     */
    protected array $plans = [
        'free' => [
            'id' => 'free',
            'name' => 'Free Starter',
            'price_inr' => 0,
            'price_usd' => 0.00,
            'billing_interval' => 'monthly',
            'max_accounts' => 1,
            'max_transactions_monthly' => 100,
            'daily_ai_queries_limit' => 20,
            'features' => [
                'multi_account' => false,
                'receipt_ocr_scanning' => false,
                'recurring_automation' => false,
                'anomaly_detection' => true,
                'audit_trail' => false,
                'export_reports' => true,
            ],
        ],
        'pro' => [
            'id' => 'pro',
            'name' => 'Finora Pro',
            'price_inr' => 799,
            'price_usd' => 9.99,
            'billing_interval' => 'monthly',
            'max_accounts' => 10,
            'max_transactions_monthly' => -1, // Unlimited
            'daily_ai_queries_limit' => 500,
            'features' => [
                'multi_account' => true,
                'receipt_ocr_scanning' => true,
                'recurring_automation' => true,
                'anomaly_detection' => true,
                'audit_trail' => true,
                'export_reports' => true,
            ],
        ],
        'enterprise' => [
            'id' => 'enterprise',
            'name' => 'Finora Enterprise & Team',
            'price_inr' => 2999,
            'price_usd' => 39.99,
            'billing_interval' => 'monthly',
            'max_accounts' => -1, // Unlimited
            'max_transactions_monthly' => -1,
            'daily_ai_queries_limit' => -1, // Unlimited
            'features' => [
                'multi_account' => true,
                'receipt_ocr_scanning' => true,
                'recurring_automation' => true,
                'anomaly_detection' => true,
                'audit_trail' => true,
                'export_reports' => true,
                'custom_exchange_rates' => true,
                'priority_queue' => true,
            ],
        ],
    ];

    /**
     * List all available SaaS plans.
     */
    public function getAvailablePlans(): array
    {
        return $this->plans;
    }

    /**
     * Get plan configuration by plan key.
     */
    public function getPlan(string $plan): array
    {
        return $this->plans[$plan] ?? $this->plans['free'];
    }

    /**
     * Get user's current subscription details, status, and remaining quota.
     */
    public function getUserSubscription(User $user): array
    {
        $planKey = $user->subscription_plan ?: 'free';
        $plan = $this->getPlan($planKey);

        // Reset daily query counter if new day
        $today = now()->toDateString();
        $userDate = !empty($user->last_ai_query_date) ? Carbon::parse($user->last_ai_query_date)->toDateString() : null;
        $dailyCount = $userDate === $today ? (int) $user->daily_ai_queries_count : 0;
        $dailyLimit = $plan['daily_ai_queries_limit'];
        $remainingQueries = $dailyLimit === -1 ? 999999 : max(0, $dailyLimit - $dailyCount);

        return [
            'plan_id' => $planKey,
            'plan_name' => $plan['name'],
            'status' => $user->subscription_status ?: 'active',
            'expires_at' => $user->subscription_expires_at,
            'daily_ai_limit' => $dailyLimit,
            'daily_ai_used' => $dailyCount,
            'daily_ai_remaining' => $remainingQueries,
            'features' => $plan['features'],
            'price_inr' => $plan['price_inr'],
            'price_usd' => $plan['price_usd'],
        ];
    }

    /**
     * Verify whether user is authorized to perform a feature-gated action.
     */
    public function canPerformAction(User $user, string $action): bool
    {
        $sub = $this->getUserSubscription($user);
        $features = $sub['features'];

        if (array_key_exists($action, $features)) {
            return (bool) $features[$action];
        }

        if ($action === 'ai_query') {
            return $sub['daily_ai_remaining'] > 0;
        }

        if ($action === 'create_account') {
            $plan = $this->getPlan($sub['plan_id']);
            if ($plan['max_accounts'] === -1) {
                return true;
            }
            return $user->accounts()->count() < $plan['max_accounts'];
        }

        return true;
    }

    /**
     * Increment and persist daily AI query usage for user.
     */
    public function recordAiQueryUsage(User $user): bool
    {
        $today = now()->toDateString();
        if ($user->last_ai_query_date !== $today) {
            $user->daily_ai_queries_count = 1;
            $user->last_ai_query_date = $today;
        } else {
            $user->daily_ai_queries_count = (int) $user->daily_ai_queries_count + 1;
        }
        $user->save();

        return true;
    }

    /**
     * Upgrade or transition a user's subscription tier.
     */
    public function upgradePlan(User $user, string $newPlan): User
    {
        if (!isset($this->plans[$newPlan])) {
            throw new \InvalidArgumentException("Invalid plan selection: {$newPlan}");
        }

        $oldPlan = $user->subscription_plan;
        $user->subscription_plan = $newPlan;
        $user->subscription_status = 'active';
        $user->subscription_expires_at = now()->addMonth();
        $user->save();

        AuditLog::record('subscription.upgrade', $user, ['plan' => $oldPlan], ['plan' => $newPlan], $user->id);

        return $user;
    }
}
