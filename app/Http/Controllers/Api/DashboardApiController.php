<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function summary(Request $request)
    {
        $user = $request->user();
        $currentMonth = now()->format('Y-m');

        return response()->json([
            'success' => true,
            'data' => [
                'total_balance' => $user->total_balance,
                'monthly_income' => $user->getMonthlyIncome($currentMonth),
                'monthly_expenses' => $user->getMonthlyExpenses($currentMonth),
                'monthly_savings' => $user->getMonthlySavings($currentMonth),
                'savings_rate_percentage' => $user->getSavingsRate($currentMonth),
                'currency' => $user->currency,
                'active_accounts_count' => $user->accounts()->where('is_active', true)->count(),
                'recent_transactions' => $user->transactions()
                    ->with(['account', 'category'])
                    ->latest('transaction_date')
                    ->take(5)
                    ->get(),
            ],
        ]);
    }
}
