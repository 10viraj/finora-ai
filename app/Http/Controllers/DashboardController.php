<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AiInsight;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now();
        $currentMonth = $now->format('Y-m');

        // Current net balance across all active accounts
        $accounts = $user->accounts()->where('is_active', true)->get();
        $totalBalance = $accounts->sum('balance');

        // Current month totals
        $monthlyIncome = $user->getMonthlyIncome($currentMonth);
        $monthlyExpenses = $user->getMonthlyExpenses($currentMonth);
        $monthlySavings = $user->getMonthlySavings($currentMonth);
        $savingsRate = $user->getSavingsRate($currentMonth);

        // All-time totals
        $allTimeIncome = (float) $user->transactions()
            ->where('type', 'income')
            ->where('status', 'completed')
            ->sum('amount');

        $allTimeExpenses = (float) $user->transactions()
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->sum('amount');

        // Recent transactions (last 8)
        $recentTransactions = $user->transactions()
            ->with(['account', 'category'])
            ->latest('transaction_date')
            ->latest('id')
            ->take(8)
            ->get();

        // Budget utilization for the current month
        $budgets = $user->budgets()
            ->with('category')
            ->where(function ($q) use ($currentMonth) {
                $q->whereNull('month_year')->orWhere('month_year', $currentMonth);
            })
            ->get();

        // 6-Month Income vs Expense Trend for Chart.js
        $trendMonths = [];
        $trendIncome = [];
        $trendExpense = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $monthKey = $month->format('Y-m');
            $trendMonths[] = $month->format('M Y');

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

            $trendIncome[] = $inc;
            $trendExpense[] = $exp;
        }

        // Category-wise Expense breakdown for current month (Doughnut chart)
        $categoryBreakdown = $user->transactions()
            ->selectRaw('categories.name as category_name, categories.color as category_color, SUM(transactions.amount) as total')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.type', 'expense')
            ->where('transactions.status', 'completed')
            ->where('transactions.transaction_date', 'like', "$currentMonth%")
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total')
            ->get();

        $categoryLabels = $categoryBreakdown->pluck('category_name');
        $categoryTotals = $categoryBreakdown->pluck('total');
        $categoryColors = $categoryBreakdown->pluck('category_color');

        // Recent high priority AI insight
        $latestInsight = $user->aiInsights()
            ->where('is_dismissed', false)
            ->first();

        // Quick categories list for transaction modal
        $categories = Category::forUser($user->id)->get();

        return view('dashboard.index', compact(
            'user',
            'accounts',
            'totalBalance',
            'monthlyIncome',
            'monthlyExpenses',
            'monthlySavings',
            'savingsRate',
            'allTimeIncome',
            'allTimeExpenses',
            'recentTransactions',
            'budgets',
            'trendMonths',
            'trendIncome',
            'trendExpense',
            'categoryLabels',
            'categoryTotals',
            'categoryColors',
            'latestInsight',
            'categories'
        ));
    }
}
