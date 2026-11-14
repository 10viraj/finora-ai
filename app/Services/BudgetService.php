<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class BudgetService
{
    /**
     * Get budgets for a given user and period filter
     */
    public function getBudgetsForPeriod(User $user, string $period = 'monthly', ?string $timeframe = null)
    {
        $now = Carbon::now();
        $query = $user->budgets()->with(['category.parent']);

        if ($period === 'yearly') {
            $year = $timeframe ?: $now->format('Y');
            $query->where('period', 'yearly')
                  ->where(function ($q) use ($year) {
                      $q->whereNull('month_year')
                        ->orWhere('month_year', 'like', "$year%");
                  });
        } else {
            // Monthly
            $month = $timeframe ?: $now->format('Y-m');
            $query->where(function ($q) use ($month) {
                $q->where('period', 'monthly')
                  ->where(function ($sub) use ($month) {
                      $sub->whereNull('month_year')
                          ->orWhere('month_year', $month);
                  });
            });
        }

        return $query->get();
    }

    /**
     * Compute budget KPI summary
     */
    public function getSummaryMetrics($budgets)
    {
        $totalBudgeted = (float) $budgets->sum('amount');
        $totalSpendingLimit = (float) $budgets->sum(fn($b) => $b->effective_limit);
        $totalSpent = (float) $budgets->sum(fn($b) => $b->spent);
        $totalRemaining = $totalSpendingLimit - $totalSpent;
        $netVariance = $totalBudgeted - $totalSpent;
        $overallPercentage = $totalSpendingLimit > 0 ? round(($totalSpent / $totalSpendingLimit) * 100, 1) : 0;

        $exceededCount = $budgets->filter(fn($b) => $b->status === 'exceeded')->count();
        $warningCount = $budgets->filter(fn($b) => $b->status === 'warning')->count();
        $normalCount = $budgets->filter(fn($b) => $b->status === 'normal')->count();

        // Calculate blended daily recommended allowance
        $dailyAllowance = $budgets->sum(fn($b) => $b->daily_budget);

        return [
            'total_budgeted' => $totalBudgeted,
            'total_spending_limit' => $totalSpendingLimit,
            'total_spent' => $totalSpent,
            'total_remaining' => $totalRemaining,
            'net_variance' => $netVariance,
            'overall_percentage' => $overallPercentage,
            'exceeded_count' => $exceededCount,
            'warning_count' => $warningCount,
            'normal_count' => $normalCount,
            'total_count' => $budgets->count(),
            'daily_allowance' => $dailyAllowance,
        ];
    }

    /**
     * Generate active alerts from current budgets
     */
    public function getActiveAlerts($budgets)
    {
        $alerts = [];

        foreach ($budgets as $budget) {
            $status = $budget->status;
            if ($status === 'exceeded') {
                $alerts[] = [
                    'id' => $budget->id,
                    'budget' => $budget,
                    'severity' => 'danger',
                    'title' => 'Spending Limit Exceeded',
                    'category_name' => $budget->category->name ?? 'General Spending',
                    'message' => "Budget '{$budget->name}' has exceeded its limit of $" . number_format($budget->effective_limit, 2) . " by $" . number_format($budget->overspent_amount, 2) . " ({$budget->utilization_percentage}% utilized).",
                    'spent' => $budget->spent,
                    'limit' => $budget->effective_limit,
                    'percent' => $budget->utilization_percentage,
                ];
            } elseif ($status === 'warning') {
                $alerts[] = [
                    'id' => $budget->id,
                    'budget' => $budget,
                    'severity' => 'warning',
                    'title' => 'Approaching Alert Threshold',
                    'category_name' => $budget->category->name ?? 'General Spending',
                    'message' => "Budget '{$budget->name}' has crossed {$budget->alert_threshold}% threshold ({$budget->utilization_percentage}% utilized). Remaining: $" . number_format(max(0, $budget->remaining), 2) . ".",
                    'spent' => $budget->spent,
                    'limit' => $budget->effective_limit,
                    'percent' => $budget->utilization_percentage,
                ];
            }
        }

        return $alerts;
    }

    /**
     * Compute Category-Wise Budget vs Actual breakdown
     * Returns all expense categories with their budget status and actual expenses
     */
    public function getCategoryWiseComparison(User $user, string $period = 'monthly', ?string $timeframe = null)
    {
        $now = Carbon::now();
        $categories = Category::forUser($user->id)
            ->where('type', 'expense')
            ->whereNull('parent_id')
            ->with(['children' => function ($q) use ($user) {
                $q->forUser($user->id);
            }])
            ->get();

        $budgets = $this->getBudgetsForPeriod($user, $period, $timeframe);
        $budgetsByCategory = $budgets->groupBy('category_id');

        $comparison = [];

        foreach ($categories as $cat) {
            $childIds = $cat->children->pluck('id')->toArray();
            $allCatIds = array_merge([$cat->id], $childIds);

            // Compute actual expenses for this category & subcategories
            $txQuery = Transaction::query()
                ->where('user_id', $user->id)
                ->where('type', 'expense')
                ->where('status', 'completed')
                ->whereIn('category_id', $allCatIds);

            if ($period === 'yearly') {
                $year = $timeframe ?: $now->format('Y');
                $txQuery->whereYear('transaction_date', $year);
            } else {
                $month = $timeframe ?: $now->format('Y-m');
                $txQuery->where('transaction_date', 'like', "$month%");
            }

            $spent = (float) $txQuery->sum('amount');

            // Find matching budget
            $matchedBudget = $budgetsByCategory->get($cat->id)?->first();
            $budgetAmount = $matchedBudget ? (float) $matchedBudget->amount : 0.0;
            $spendingLimit = $matchedBudget ? (float) $matchedBudget->effective_limit : 0.0;
            $hasBudget = $matchedBudget !== null;

            $utilization = 0.0;
            if ($spendingLimit > 0) {
                $utilization = round(($spent / $spendingLimit) * 100, 1);
            } elseif ($budgetAmount > 0) {
                $utilization = round(($spent / $budgetAmount) * 100, 1);
            }

            $variance = $hasBudget ? ($budgetAmount - $spent) : -$spent;
            $isFavorable = $variance >= 0;

            $status = 'unbudgeted';
            if ($hasBudget) {
                $status = $matchedBudget->status;
            }

            $comparison[] = [
                'category_id' => $cat->id,
                'category_name' => $cat->name,
                'icon' => $cat->icon ?? 'bi-tag',
                'color' => $cat->color ?? '#6366f1',
                'subcategories_count' => count($childIds),
                'has_budget' => $hasBudget,
                'budget_id' => $matchedBudget?->id,
                'budget_name' => $matchedBudget?->name ?? $cat->name . ' Budget',
                'budget_amount' => $budgetAmount,
                'spending_limit' => $spendingLimit,
                'spent' => $spent,
                'variance_amount' => $variance,
                'variance_percentage' => $budgetAmount > 0 ? round((($spent - $budgetAmount) / $budgetAmount) * 100, 1) : null,
                'utilization_percentage' => $utilization,
                'is_favorable' => $isFavorable,
                'status' => $status,
                'alert_threshold' => $matchedBudget?->alert_threshold ?? 80,
            ];
        }

        // Also check if there's a General / All Categories budget
        $generalBudgets = $budgets->whereNull('category_id');
        foreach ($generalBudgets as $gb) {
            $comparison[] = [
                'category_id' => null,
                'category_name' => 'General / All Categories',
                'icon' => 'bi-pie-chart',
                'color' => '#3b82f6',
                'subcategories_count' => 0,
                'has_budget' => true,
                'budget_id' => $gb->id,
                'budget_name' => $gb->name,
                'budget_amount' => (float) $gb->amount,
                'spending_limit' => (float) $gb->effective_limit,
                'spent' => $gb->spent,
                'variance_amount' => $gb->variance_amount,
                'variance_percentage' => $gb->variance_percentage,
                'utilization_percentage' => $gb->utilization_percentage,
                'is_favorable' => $gb->is_favorable,
                'status' => $gb->status,
                'alert_threshold' => $gb->alert_threshold,
            ];
        }

        return collect($comparison)->sortByDesc(fn($c) => $c['spent'])->values();
    }

    /**
     * Alias for getCategoryWiseComparison
     */
    public function getComparisonData(User $user, string $period = 'monthly', ?string $timeframe = null)
    {
        return $this->getCategoryWiseComparison($user, $period, $timeframe);
    }

    /**
     * Prepare Chart.js dataset for Budget vs Actual visual comparison
     */
    public function getChartData($comparisonData)
    {
        $filtered = $comparisonData->filter(fn($c) => $c['has_budget'] || $c['spent'] > 0);

        $labels = [];
        $budgetData = [];
        $actualData = [];
        $colors = [];

        foreach ($filtered as $item) {
            $labels[] = $item['category_name'];
            $budgetData[] = $item['budget_amount'];
            $actualData[] = $item['spent'];
            $colors[] = $item['color'];
        }

        return [
            'labels' => $labels,
            'budget' => $budgetData,
            'actual' => $actualData,
            'colors' => $colors,
        ];
    }

    /**
     * Check if a newly recorded or updated transaction triggers a budget alert
     */
    public function checkTransactionAlert(User $user, Transaction $transaction): ?array
    {
        if ($transaction->type !== 'expense' || $transaction->status !== 'completed') {
            return null;
        }

        $date = Carbon::parse($transaction->transaction_date);
        $month = $date->format('Y-m');
        $year = $date->format('Y');

        // Check matching monthly and yearly budgets
        $budgets = $user->budgets()
            ->where(function ($q) use ($transaction) {
                if ($transaction->category_id) {
                    // Check if category or parent matches
                    $cat = Category::find($transaction->category_id);
                    $parentCatId = $cat?->parent_id;
                    $catIds = array_filter([$transaction->category_id, $parentCatId]);
                    $q->whereIn('category_id', $catIds)->orWhereNull('category_id');
                } else {
                    $q->whereNull('category_id');
                }
            })
            ->where(function ($q) use ($month, $year) {
                $q->where(function ($mQ) use ($month) {
                    $mQ->where('period', 'monthly')
                       ->where(function ($sub) use ($month) {
                           $sub->whereNull('month_year')->orWhere('month_year', $month);
                       });
                })->orWhere(function ($yQ) use ($year) {
                    $yQ->where('period', 'yearly')
                       ->where(function ($sub) use ($year) {
                           $sub->whereNull('month_year')->orWhere('month_year', 'like', "$year%");
                       });
                });
            })
            ->get();

        foreach ($budgets as $budget) {
            $status = $budget->status;
            if ($status === 'exceeded') {
                return [
                    'severity' => 'danger',
                    'message' => "Budget limit exceeded for '{$budget->name}'! Total spent: $" . number_format($budget->spent, 2) . " / Limit: $" . number_format($budget->effective_limit, 2) . " (Exceeded by $" . number_format($budget->overspent_amount, 2) . ").",
                ];
            } elseif ($status === 'warning') {
                return [
                    'severity' => 'warning',
                    'message' => "Budget alert: '{$budget->name}' has crossed {$budget->alert_threshold}% threshold! Total spent: $" . number_format($budget->spent, 2) . " / Limit: $" . number_format($budget->effective_limit, 2) . " ({$budget->utilization_percentage}% utilized).",
                ];
            }
        }

        return null;
    }
}

