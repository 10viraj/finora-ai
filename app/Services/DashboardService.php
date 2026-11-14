<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Get aggregate KPI metrics for dashboard.
     */
    public function getMetrics(User $user, ?string $monthYear = null): array
    {
        $targetMonth = $monthYear ?: now()->format('Y-m');

        $totalBalance = (float) $user->accounts()->where('is_active', true)->sum('balance');
        $monthlyIncome = $user->getMonthlyIncome($targetMonth);
        $monthlyExpenses = $user->getMonthlyExpenses($targetMonth);
        $netSavings = $user->getMonthlySavings($targetMonth);
        $savingsRate = $user->getSavingsRate($targetMonth);

        // Previous month comparison
        $prevMonth = Carbon::parse($targetMonth . '-01')->subMonth()->format('Y-m');
        $prevExpenses = $user->getMonthlyExpenses($prevMonth);
        $expenseGrowth = $prevExpenses > 0 ? round((($monthlyExpenses - $prevExpenses) / $prevExpenses) * 100, 1) : 0;

        return [
            'total_balance' => $totalBalance,
            'monthly_income' => $monthlyIncome,
            'monthly_expenses' => $monthlyExpenses,
            'net_savings' => $netSavings,
            'savings_rate' => $savingsRate,
            'expense_growth' => $expenseGrowth,
            'currency' => $user->currency ?: 'INR',
        ];
    }

    /**
     * Get recent transactions for dashboard view.
     */
    public function getRecentTransactions(User $user, int $limit = 10)
    {
        return $user->transactions()
            ->with(['account', 'category', 'paymentMethod'])
            ->latest('transaction_date')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user account cards summary.
     */
    public function getAccountSummaries(User $user)
    {
        return $user->accounts()->where('is_active', true)->get();
    }
}

