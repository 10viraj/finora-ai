<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class ReportService
{
    /**
     * Build comprehensive report dataset for daily, weekly, or monthly periods
     */
    public function getReportData(User $user, string $period = 'monthly', ?string $timeframe = null): array
    {
        $now = Carbon::now();
        $period = in_array($period, ['daily', 'weekly', 'monthly', 'yearly']) ? $period : 'monthly';

        // 1. Resolve Date Range based on period
        $dateRange = $this->resolveDateRange($period, $timeframe);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];
        $label = $dateRange['label'];
        $formattedDate = $dateRange['formatted'];

        // 2. Fetch Transactions for Period
        $transactionsQuery = $user->transactions()
            ->with(['account', 'category.parent'])
            ->where('status', 'completed')
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate);

        $transactions = (clone $transactionsQuery)->latest('transaction_date')->latest('id')->get();

        // 3. Financial Metrics
        $income = (float) (clone $transactionsQuery)->where('type', 'income')->sum('amount');
        $expense = (float) (clone $transactionsQuery)->where('type', 'expense')->sum('amount');
        $savings = $income - $expense;
        $savingsRate = $income > 0 ? round(($savings / $income) * 100, 1) : 0;

        $txCount = $transactions->count();
        $daysInRange = max(1, Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1);
        $dailyAvgSpend = round($expense / $daysInRange, 2);

        $largestExpense = $transactions->where('type', 'expense')->sortByDesc('amount')->first();

        // 4. Category-Wise Expense Breakdown
        $categoryBreakdown = $this->getCategoryBreakdown($user, $startDate, $endDate, $expense);

        // 5. Income and Expense Trends
        $trendData = $this->getTrendData($user, $period, $startDate, $endDate);

        // 6. Savings Analysis
        $savingsAnalysis = $this->getSavingsAnalysis($user, $income, $expense, $savings, $savingsRate, $period);

        return [
            'period' => $period,
            'timeframe' => $timeframe,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'label' => $label,
            'formatted_date' => $formattedDate,
            'days_in_range' => $daysInRange,
            'income' => $income,
            'expense' => $expense,
            'savings' => $savings,
            'savings_rate' => $savingsRate,
            'daily_avg_spend' => $dailyAvgSpend,
            'transactions_count' => $txCount,
            'largest_expense' => $largestExpense,
            'transactions' => $transactions,
            'category_breakdown' => $categoryBreakdown,
            'trend_data' => $trendData,
            'savings_analysis' => $savingsAnalysis,
        ];
    }

    /**
     * Resolve start and end dates based on timeframe
     */
    protected function resolveDateRange(string $period, ?string $timeframe): array
    {
        $now = Carbon::now();

        if ($period === 'daily') {
            $dateStr = $timeframe ?: $now->format('Y-m-d');
            $date = Carbon::parse($dateStr);
            return [
                'start' => $date->format('Y-m-d'),
                'end' => $date->format('Y-m-d'),
                'label' => 'Daily Report',
                'formatted' => $date->format('l, F j, Y'),
            ];
        }

        if ($period === 'weekly') {
            // timeframe could be a specific date or week string e.g. 2026-W46 or Y-m-d
            $date = $timeframe ? Carbon::parse($timeframe) : $now;
            $startOfWeek = $date->copy()->startOfWeek();
            $endOfWeek = $date->copy()->endOfWeek();
            return [
                'start' => $startOfWeek->format('Y-m-d'),
                'end' => $endOfWeek->format('Y-m-d'),
                'label' => 'Weekly Report',
                'formatted' => $startOfWeek->format('M d') . ' - ' . $endOfWeek->format('M d, Y'),
            ];
        }

        if ($period === 'yearly') {
            $yearStr = $timeframe ? substr($timeframe, 0, 4) : $now->format('Y');
            try {
                $dt = Carbon::createFromFormat('Y', $yearStr);
            } catch (\Exception $e) {
                $dt = $now->copy();
            }
            return [
                'start' => $dt->copy()->startOfYear()->format('Y-m-d'),
                'end' => $dt->copy()->endOfYear()->format('Y-m-d'),
                'label' => 'Annual Report',
                'formatted' => $dt->format('Y'),
            ];
        }

        // Monthly
        $monthStr = $timeframe ?: $now->format('Y-m');
        try {
            $dt = Carbon::createFromFormat('Y-m', $monthStr);
        } catch (\Exception $e) {
            $dt = $now->copy();
            $monthStr = $now->format('Y-m');
        }

        return [
            'start' => $dt->copy()->startOfMonth()->format('Y-m-d'),
            'end' => $dt->copy()->endOfMonth()->format('Y-m-d'),
            'label' => 'Monthly Report',
            'formatted' => $dt->format('F Y'),
        ];
    }

    /**
     * Category-wise expenses rollup and breakdown
     */
    protected function getCategoryBreakdown(User $user, string $startDate, string $endDate, float $totalExpense): array
    {
        $categories = Category::forUser($user->id)
            ->where('type', 'expense')
            ->whereNull('parent_id')
            ->with(['children' => function ($q) use ($user) {
                $q->forUser($user->id);
            }])
            ->get();

        $breakdown = [];

        foreach ($categories as $cat) {
            $childIds = $cat->children->pluck('id')->toArray();
            $allCatIds = array_merge([$cat->id], $childIds);

            $query = Transaction::query()
                ->where('user_id', $user->id)
                ->where('type', 'expense')
                ->where('status', 'completed')
                ->whereIn('category_id', $allCatIds)
                ->whereDate('transaction_date', '>=', $startDate)
                ->whereDate('transaction_date', '<=', $endDate);

            $spent = (float) $query->sum('amount');
            $count = $query->count();
            $avgAmount = $count > 0 ? round($spent / $count, 2) : 0;
            $share = $totalExpense > 0 ? round(($spent / $totalExpense) * 100, 1) : 0;

            if ($spent > 0) {
                $breakdown[] = [
                    'category_id' => $cat->id,
                    'name' => $cat->name,
                    'icon' => $cat->icon ?? 'bi-tag',
                    'color' => $cat->color ?? '#6366f1',
                    'subcategories_count' => count($childIds),
                    'total_spent' => $spent,
                    'transaction_count' => $count,
                    'average_transaction' => $avgAmount,
                    'share_percentage' => $share,
                ];
            }
        }

        // Check for general uncategorized expenses
        $uncategorizedQuery = Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->whereNull('category_id')
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate);

        $uncatSpent = (float) $uncategorizedQuery->sum('amount');
        if ($uncatSpent > 0) {
            $count = $uncategorizedQuery->count();
            $breakdown[] = [
                'category_id' => null,
                'name' => 'General / Uncategorized',
                'icon' => 'bi-question-circle',
                'color' => '#94a3b8',
                'subcategories_count' => 0,
                'total_spent' => $uncatSpent,
                'transaction_count' => $count,
                'average_transaction' => round($uncatSpent / $count, 2),
                'share_percentage' => $totalExpense > 0 ? round(($uncatSpent / $totalExpense) * 100, 1) : 0,
            ];
        }

        return collect($breakdown)->sortByDesc('total_spent')->values()->toArray();
    }

    /**
     * Compute timeseries trend data for Income vs Expense charts
     */
    protected function getTrendData(User $user, string $period, string $startDate, string $endDate): array
    {
        $labels = [];
        $incomeSeries = [];
        $expenseSeries = [];
        $netSeries = [];

        if ($period === 'daily' || $period === 'weekly') {
            // Daily breakdown across the timeframe (or past 7 days for daily)
            $start = $period === 'daily' ? Carbon::parse($startDate)->subDays(6) : Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            $current = $start->copy();
            while ($current->lte($end)) {
                $dayStr = $current->format('Y-m-d');
                $labels[] = $current->format('M d (D)');

                $inc = (float) $user->transactions()
                    ->where('type', 'income')
                    ->where('status', 'completed')
                    ->whereDate('transaction_date', $dayStr)
                    ->sum('amount');

                $exp = (float) $user->transactions()
                    ->where('type', 'expense')
                    ->where('status', 'completed')
                    ->whereDate('transaction_date', $dayStr)
                    ->sum('amount');

                $incomeSeries[] = $inc;
                $expenseSeries[] = $exp;
                $netSeries[] = $inc - $exp;

                $current->addDay();
            }
        } elseif ($period === 'yearly') {
            // Yearly view: 12-Month breakdown of the selected year
            $year = Carbon::parse($startDate)->format('Y');
            for ($m = 1; $m <= 12; $m++) {
                $dt = Carbon::createFromDate((int) $year, $m, 1);
                $monthKey = $dt->format('Y-m');
                $labels[] = $dt->format('M');

                $inc = (float) $user->transactions()
                    ->where('type', 'income')
                    ->where('status', 'completed')
                    ->where('transaction_date', 'like', "$monthKey%")
                    ->sum('amount');

                $exp = (float) $user->transactions()
                    ->where('type', 'expense')
                    ->where('status', 'completed')
                    ->where('transaction_date', 'like', "$monthKey%")
                    ->sum('amount');

                $incomeSeries[] = $inc;
                $expenseSeries[] = $exp;
                $netSeries[] = $inc - $exp;
            }
        } else {
            // Monthly view: 6-Month Historical Trajectory
            $endMonth = Carbon::parse($endDate);
            for ($i = 5; $i >= 0; $i--) {
                $month = $endMonth->copy()->subMonths($i);
                $monthKey = $month->format('Y-m');
                $labels[] = $month->format('M Y');

                $inc = (float) $user->transactions()
                    ->where('type', 'income')
                    ->where('status', 'completed')
                    ->where('transaction_date', 'like', "$monthKey%")
                    ->sum('amount');

                $exp = (float) $user->transactions()
                    ->where('type', 'expense')
                    ->where('status', 'completed')
                    ->where('transaction_date', 'like', "$monthKey%")
                    ->sum('amount');

                $incomeSeries[] = $inc;
                $expenseSeries[] = $exp;
                $netSeries[] = $inc - $exp;
            }
        }

        return [
            'labels' => $labels,
            'income' => $incomeSeries,
            'expense' => $expenseSeries,
            'net' => $netSeries,
        ];
    }

    /**
     * Savings Analysis and wealth accumulation performance
     */
    protected function getSavingsAnalysis(User $user, float $income, float $expense, float $savings, float $savingsRate, string $period): array
    {
        // Status benchmark
        $status = 'healthy';
        $statusLabel = 'Optimal Savings Rate';
        $benchmarkTarget = 25.0; // 25% target

        if ($savingsRate >= 25.0) {
            $status = 'excellent';
            $statusLabel = 'Superior Savings Retention (Above 25% Target)';
        } elseif ($savingsRate >= 10.0) {
            $status = 'moderate';
            $statusLabel = 'Moderate Savings (Room for Optimization to 25%)';
        } elseif ($savingsRate >= 0.0) {
            $status = 'warning';
            $statusLabel = 'Low Savings Retention (Under 10%)';
        } else {
            $status = 'critical';
            $statusLabel = 'Deficit / Negative Cash Flow (Outflows Exceed Revenue)';
        }

        // Projected annual savings
        $projectedAnnualSavings = 0.0;
        if ($period === 'yearly') {
            $projectedAnnualSavings = round($savings, 2);
        } elseif ($period === 'monthly') {
            $projectedAnnualSavings = round($savings * 12, 2);
        } elseif ($period === 'weekly') {
            $projectedAnnualSavings = round($savings * 52, 2);
        } else {
            $projectedAnnualSavings = round($savings * 365, 2);
        }

        // All-time savings
        $allTimeIncome = (float) $user->transactions()->where('type', 'income')->where('status', 'completed')->sum('amount');
        $allTimeExpense = (float) $user->transactions()->where('type', 'expense')->where('status', 'completed')->sum('amount');
        $cumulativeSavings = $allTimeIncome - $allTimeExpense;
        $cumulativeSavingsRate = $allTimeIncome > 0 ? round(($cumulativeSavings / $allTimeIncome) * 100, 1) : 0;

        return [
            'net_savings' => $savings,
            'savings_rate' => $savingsRate,
            'status' => $status,
            'status_label' => $statusLabel,
            'benchmark_target' => $benchmarkTarget,
            'variance_from_benchmark' => round($savingsRate - $benchmarkTarget, 1),
            'projected_annual_savings' => $projectedAnnualSavings,
            'cumulative_savings' => $cumulativeSavings,
            'cumulative_savings_rate' => $cumulativeSavingsRate,
        ];
    }
}

