<?php

namespace App\Services\AI;

use App\Models\AiInsight;
use App\Models\AiRecommendation;
use App\Models\Category;
use App\Models\User;

class InsightGeneratorService
{
    public function __construct(
        protected FinancialAnalysisService $analysisService
    ) {}

    /**
     * Scan transactions and generate actionable anomaly insights, spending analysis,
     * and budget recommendations.
     */
    public function generateInsights(User $user): array
    {
        $insights = [];
        $summary = $this->analysisService->getAuthorizedFinancialSummary($user);
        $sym = $summary['currency_symbol'];
        $bRec = $summary['budget_recommendation'];
        $highest = $summary['highest_category'];

        // 1. Detect anomalous transactions (>2x historical category average)
        $recentExpenses = $user->transactions()
            ->where('type', 'expense')
            ->where('transaction_date', '>=', now()->subDays(30))
            ->get();

        $avgAmount = $recentExpenses->avg('amount') ?: 100;

        foreach ($recentExpenses as $tx) {
            if ($tx->amount > ($avgAmount * 2.5) && $tx->amount > 500) {
                $insight = $user->aiInsights()->create([
                    'insight_type' => 'unusual_expense',
                    'type' => 'spending_anomaly',
                    'title' => "Unusual Expense Detected: {$tx->description}",
                    'description' => "Transaction of {$sym}" . number_format($tx->amount, 0) . " is significantly higher than your 30-day average expense of {$sym}" . number_format($avgAmount, 0) . ".",
                    'summary' => "Anomaly on {$tx->description} ({$sym}" . number_format($tx->amount, 0) . ")",
                    'period_start' => now()->subDays(30)->toDateString(),
                    'period_end' => now()->toDateString(),
                    'priority' => 'high',
                    'data_snapshot' => [
                        'transaction_id' => $tx->id,
                        'amount' => $tx->amount,
                        'average' => round($avgAmount, 2),
                    ],
                    'generated_at' => now(),
                ]);

                $insights[] = $insight;
                break; // Top anomaly
            }
        }

        // 2. Generate AI Spending Analysis Insights
        foreach ($summary['spending_analysis'] as $idx => $statement) {
            $insight = $user->aiInsights()->create([
                'insight_type' => 'spending_analysis',
                'type' => 'spending_analysis',
                'title' => "Spending Analysis: " . explode(' ', $statement)[0] . " Trend",
                'description' => $statement,
                'summary' => $statement,
                'period_start' => now()->startOfMonth()->toDateString(),
                'period_end' => now()->toDateString(),
                'priority' => 'medium',
                'data_snapshot' => [
                    'analysis_point' => $statement,
                    'highest_category' => $highest,
                    'savings_rate' => $summary['current_month']['savings_rate'],
                ],
                'generated_at' => now(),
            ]);
            $insights[] = $insight;
        }

        // 3. Generate AI Budget Recommendations
        $recTitle = "Monthly Budget Recommendation: Target {$sym}" . number_format($bRec['suggested_savings_target'], 0);
        $recSummary = "Monthly income: {$bRec['formatted']['income']} | Monthly expenses: {$bRec['formatted']['expenses']} | Savings: {$bRec['formatted']['savings']}. AI suggestion: {$bRec['ai_suggestion']}";

        $budgetInsight = $user->aiInsights()->create([
            'insight_type' => 'budget_recommendation',
            'type' => 'budget_recommendation',
            'title' => $recTitle,
            'description' => $recSummary,
            'summary' => $recSummary,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'priority' => 'high',
            'action_suggested' => $bRec['ai_suggestion'],
            'data_snapshot' => $bRec,
            'metrics_snapshot' => [
                'monthly_income' => $bRec['monthly_income'],
                'monthly_expenses' => $bRec['monthly_expenses'],
                'savings' => $bRec['savings'],
                'suggested_amount' => $bRec['suggested_expense_cap'],
                'suggested_limit' => $bRec['suggested_expense_cap'],
                'suggested_savings_target' => $bRec['suggested_savings_target'],
            ],
            'generated_at' => now(),
        ]);
        $insights[] = $budgetInsight;

        // Create persistent record in ai_recommendations table
        $user->aiRecommendations()->create([
            'insight_id' => $budgetInsight->id,
            'title' => $recTitle,
            'recommendation_type' => 'budget_cap',
            'suggested_amount' => $bRec['suggested_expense_cap'],
            'potential_savings' => $bRec['needed_savings_increase'],
            'priority' => 'high',
            'status' => 'pending',
            'action_payload' => $bRec,
        ]);

        return $insights;
    }
}
