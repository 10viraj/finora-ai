<?php

namespace App\Services;

use App\Models\AiInsight;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\UnusualExpenseNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AnomalyDetectionService
{
    /**
     * Evaluate a transaction against statistical historical baselines and record an anomaly if triggered.
     * AI explains the statistical deviation without independently labeling transactions as fraud.
     */
    public function evaluateTransaction(Transaction $tx): ?array
    {
        if ($tx->type !== 'expense' || $tx->amount <= 0) {
            return null;
        }

        $user = $tx->user ?: User::find($tx->user_id);
        if (!$user) {
            return null;
        }

        $amount = (float) $tx->amount;
        $catId = $tx->category_id;
        $catName = $tx->category?->name ?? 'General Spending';
        $sym = $user->currency === 'INR' ? '₹' : ($user->currency === 'USD' ? '$' : $user->currency);

        // 1. Calculate historical 90-day category average
        $ninetyDaysAgo = Carbon::parse($tx->transaction_date)->subDays(90)->format('Y-m-d');
        $histStats = Transaction::where('user_id', $user->id)
            ->where('category_id', $catId)
            ->where('type', 'expense')
            ->where('id', '!=', $tx->id)
            ->whereDate('transaction_date', '>=', $ninetyDaysAgo)
            ->whereDate('transaction_date', '<=', $tx->transaction_date)
            ->selectRaw('AVG(amount) as avg_amount, COUNT(*) as count')
            ->first();

        $histAvg = $histStats && $histStats->count > 0 ? (float) $histStats->avg_amount : 0;

        $isAnomaly = false;
        $reason = '';
        $multiplier = 1.0;

        // Rule 1: Category Average Multiplier (Amount > 2.5x historical average for this category)
        if ($histAvg > 0 && $amount >= ($histAvg * 2.5) && $amount >= 100) {
            $isAnomaly = true;
            $multiplier = round($amount / $histAvg, 1);
            $reason = "Expense of {$sym}" . number_format($amount, 2) . " on '{$tx->description}' is {$multiplier}x higher than your 90-day category average ({$sym}" . number_format($histAvg, 2) . ") for {$catName}.";
        } else {
            // Rule 2: High percentage of monthly outlay (> 30% of current month's expenses)
            $month = Carbon::parse($tx->transaction_date)->format('Y-m');
            $monthlyTotal = (float) Transaction::where('user_id', $user->id)
                ->where('type', 'expense')
                ->where('transaction_date', 'like', "$month%")
                ->sum('amount');

            if ($monthlyTotal > 0 && ($amount / $monthlyTotal) >= 0.30 && $amount >= 500) {
                $isAnomaly = true;
                $pct = round(($amount / $monthlyTotal) * 100, 1);
                $multiplier = round($pct / 10, 1);
                $reason = "Single outlay of {$sym}" . number_format($amount, 2) . " accounts for {$pct}% of your total expenditures this month.";
            }
        }

        if (!$isAnomaly) {
            return null;
        }

        // AI explanation grounded in statistical mathematics, without labeling as fraud
        $aiExplanation = "Statistical Observation: {$reason} This has been flagged for your periodic review and budget recalibration, not independently labeled as fraudulent activity.";

        // Record AiInsight
        $insight = AiInsight::create([
            'user_id' => $user->id,
            'insight_type' => 'unusual_expense',
            'type' => 'spending_anomaly',
            'title' => "Unusual Expense Detected: {$tx->description}",
            'summary' => $reason,
            'description' => $aiExplanation,
            'period_start' => Carbon::parse($tx->transaction_date)->startOfMonth()->toDateString(),
            'period_end' => Carbon::parse($tx->transaction_date)->endOfMonth()->toDateString(),
            'data_snapshot' => [
                'transaction_id' => $tx->id,
                'amount' => $amount,
                'historical_category_average' => $histAvg,
                'multiplier' => $multiplier,
                'category' => $catName,
                'fraud_label' => 'not_labeled_as_fraud',
            ],
            'generated_at' => now(),
            'priority' => 'medium',
            'action_suggested' => 'Verify this expenditure aligns with your planned capital or discretionary budget.',
        ]);

        // Dispatch Notification to user
        try {
            $user->notify(new UnusualExpenseNotification($tx, $histAvg));
        } catch (\Throwable $e) {
            Log::info('Notification dispatch fallback: ' . $e->getMessage());
        }

        return [
            'is_anomaly' => true,
            'transaction_id' => $tx->id,
            'amount' => $amount,
            'historical_average' => $histAvg,
            'multiplier' => $multiplier,
            'reason' => $reason,
            'ai_explanation' => $aiExplanation,
            'insight_id' => $insight->id,
        ];
    }
}

