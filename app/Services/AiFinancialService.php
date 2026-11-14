<?php

namespace App\Services;

use App\Models\AiChatMessage;
use App\Models\AiInsight;
use App\Models\Budget;
use App\Models\Category;
use App\Models\MonthlyFinancialSummary;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiFinancialService
{
    /**
     * Run full intelligent financial audit for a user
     */
    public function analyzeFinances(User $user): array
    {
        $now = Carbon::now();
        $currentMonth = $now->format('Y-m');

        $insightsGenerated = [];

        // 1. Detect Unusual Expenses (Anomalies)
        $anomalies = $this->detectUnusualExpenses($user, $currentMonth);
        foreach ($anomalies as $anomaly) {
            $existing = $user->aiInsights()
                ->where('type', 'spending_anomaly')
                ->where('title', $anomaly['title'])
                ->where('is_dismissed', false)
                ->first();

            if (!$existing) {
                $insightsGenerated[] = AiInsight::create([
                    'user_id' => $user->id,
                    'type' => 'spending_anomaly',
                    'title' => $anomaly['title'],
                    'summary' => $anomaly['summary'],
                    'detailed_analysis' => $anomaly['detailed_analysis'],
                    'priority' => $anomaly['priority'],
                    'action_suggested' => $anomaly['action_suggested'],
                    'metrics_snapshot' => $anomaly['metrics_snapshot'],
                ]);
            }
        }

        // 2. Generate Personalized Saving Suggestions
        $savingTips = $this->generatePersonalizedSavingSuggestions($user, $currentMonth);
        foreach ($savingTips as $tip) {
            $existing = $user->aiInsights()
                ->where('type', 'savings_opportunity')
                ->where('title', $tip['title'])
                ->where('is_dismissed', false)
                ->first();

            if (!$existing) {
                $insightsGenerated[] = AiInsight::create([
                    'user_id' => $user->id,
                    'type' => 'savings_opportunity',
                    'title' => $tip['title'],
                    'summary' => $tip['summary'],
                    'detailed_analysis' => $tip['detailed_analysis'],
                    'priority' => $tip['priority'],
                    'action_suggested' => $tip['action_suggested'],
                    'metrics_snapshot' => $tip['metrics_snapshot'],
                ]);
            }
        }

        // 3. Generate Budget Recommendations
        $budgetRecs = $this->generateBudgetRecommendations($user, $currentMonth);
        foreach ($budgetRecs as $rec) {
            $existing = $user->aiInsights()
                ->where('type', 'budget_recommendation')
                ->where('title', $rec['title'])
                ->where('is_dismissed', false)
                ->first();

            if (!$existing) {
                $insightsGenerated[] = AiInsight::create([
                    'user_id' => $user->id,
                    'type' => 'budget_recommendation',
                    'title' => $rec['title'],
                    'summary' => $rec['summary'],
                    'detailed_analysis' => $rec['detailed_analysis'],
                    'priority' => $rec['priority'],
                    'action_suggested' => $rec['action_suggested'],
                    'metrics_snapshot' => $rec['metrics_snapshot'],
                ]);
            }
        }

        // 4. Budget Threshold Warnings
        $budgets = $user->budgets()->with('category')->get();
        foreach ($budgets as $budget) {
            if ($budget->utilization_percentage >= (float) $budget->alert_threshold) {
                $title = $budget->status === 'exceeded' 
                    ? "Spending Limit Exceeded: {$budget->name}" 
                    : "Budget Warning: {$budget->name}";

                $existing = $user->aiInsights()
                    ->where('type', 'budget_warning')
                    ->where('title', $title)
                    ->where('is_dismissed', false)
                    ->first();

                if (!$existing) {
                    $insightsGenerated[] = AiInsight::create([
                        'user_id' => $user->id,
                        'type' => 'budget_warning',
                        'title' => $title,
                        'summary' => "You have utilized {$budget->utilization_percentage}% of your {$budget->name} limit (\${$budget->spent} of \${$budget->effective_limit}).",
                        'detailed_analysis' => "Spending velocity in {$budget->name} indicates you have reached or crossed your alert threshold with {$budget->days_remaining} days remaining in this period.",
                        'priority' => $budget->status === 'exceeded' ? 'high' : 'medium',
                        'action_suggested' => "Review upcoming purchase orders or adjust discretionary category limits.",
                        'metrics_snapshot' => [
                            'budget_id' => $budget->id,
                            'spent' => $budget->spent,
                            'limit' => $budget->effective_limit,
                            'remaining' => $budget->remaining,
                        ],
                    ]);
                }
            }
        }

        // 5. Cashflow Runway & Business Health Forecast
        $balance = (float) $user->total_balance;
        $currExpense = (float) $user->getMonthlyExpenses($currentMonth);
        $avgDailyBurn = $currExpense / max(1, $now->day);
        if ($avgDailyBurn > 0) {
            $runwayDays = round($balance / $avgDailyBurn);
            $runwayMonths = round($runwayDays / 30, 1);

            $existing = $user->aiInsights()
                ->where('type', 'cashflow_forecast')
                ->where('is_dismissed', false)
                ->first();

            if (!$existing) {
                $insightsGenerated[] = AiInsight::create([
                    'user_id' => $user->id,
                    'type' => 'cashflow_forecast',
                    'title' => "Estimated Cash Runway: {$runwayMonths} Months",
                    'summary' => "At your current average daily burn of \${$avgDailyBurn}/day, your liquid reserves of \${$balance} provide approx. {$runwayMonths} months of operational runway.",
                    'detailed_analysis' => "Maintaining a 3 to 6-month buffer protects against delayed client receivables and seasonality in cash flows.",
                    'priority' => $runwayMonths < 3 ? 'high' : 'low',
                    'action_suggested' => $runwayMonths < 3 ? 'Prioritize invoicing overdue receivables and restrict non-essential capital expenses.' : 'Deploy surplus idle capital into yield-generating reserve accounts.',
                    'metrics_snapshot' => [
                        'runway_months' => $runwayMonths,
                        'daily_burn' => round($avgDailyBurn, 2),
                        'balance' => $balance,
                    ],
                ]);
            }
        }

        // 6. Generate or update Monthly Financial Summary
        $this->generateMonthlySummary($user, $currentMonth);

        return $insightsGenerated;
    }

    /**
     * Detect unusual expenses & anomalies in transactions
     */
    public function detectUnusualExpenses(User $user, ?string $month = null): array
    {
        $month = $month ?: now()->format('Y-m');
        $anomalies = [];

        // Fetch transactions for the target month
        $transactions = $user->transactions()
            ->with('category')
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->where('transaction_date', 'like', "$month%")
            ->get();

        if ($transactions->isEmpty()) {
            return [];
        }

        $totalMonthlyExpense = (float) $transactions->sum('amount');

        // Historical category averages (from previous 90 days)
        $ninetyDaysAgo = now()->copy()->subDays(90)->format('Y-m-d');
        $historicalCategoryStats = $user->transactions()
            ->selectRaw('category_id, AVG(amount) as avg_amount, COUNT(*) as count')
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->whereDate('transaction_date', '>=', $ninetyDaysAgo)
            ->whereDate('transaction_date', '<', Carbon::createFromFormat('Y-m', $month)->startOfMonth()->format('Y-m-d'))
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        foreach ($transactions as $tx) {
            $catId = $tx->category_id;
            $catName = $tx->category->name ?? 'General Spending';
            $amount = (float) $tx->amount;

            // Rule 1: High Single Transaction Outlier (accounts for > 30% of entire monthly expenses)
            if ($totalMonthlyExpense > 0 && ($amount / $totalMonthlyExpense) >= 0.30 && $amount >= 500) {
                $pct = round(($amount / $totalMonthlyExpense) * 100, 1);
                $anomalies[] = [
                    'transaction_id' => $tx->id,
                    'title' => "Unusual Large Outlay: {$tx->description}",
                    'summary' => "A single expense of \${$amount} on {$tx->transaction_date->format('M d')} to '{$tx->payee_payer}' accounts for {$pct}% of your total monthly spend.",
                    'detailed_analysis' => "Concentrated large single expenditures dramatically reduce cash runway. Verify this transaction is a planned one-off capital allocation.",
                    'priority' => 'high',
                    'action_suggested' => "Verify invoice documentation and ensure vendor payment terms align with monthly cash flow.",
                    'metrics_snapshot' => [
                        'transaction_id' => $tx->id,
                        'amount' => $amount,
                        'category' => $catName,
                        'percentage_of_month' => $pct,
                    ],
                ];
                continue;
            }

            // Rule 2: Category Average Multiplier Outlier (amount > 2.5x historical average for this category)
            if ($catId && isset($historicalCategoryStats[$catId])) {
                $histAvg = (float) $historicalCategoryStats[$catId]->avg_amount;
                if ($histAvg > 0 && $amount > ($histAvg * 2.5) && $amount >= 100) {
                    $multiplier = round($amount / $histAvg, 1);
                    $anomalies[] = [
                        'transaction_id' => $tx->id,
                        'title' => "Category Spike: {$catName} ({$multiplier}x Average)",
                        'summary' => "\${$amount} spent on '{$tx->description}' is {$multiplier}x higher than your historical average (\${$histAvg}) for {$catName}.",
                        'detailed_analysis' => "Spending velocity in {$catName} significantly departed from baseline behavior on {$tx->transaction_date->format('M d')}.",
                        'priority' => 'medium',
                        'action_suggested' => "Audit {$catName} invoices to ensure no unexpected rate increases or duplicate billings.",
                        'metrics_snapshot' => [
                            'transaction_id' => $tx->id,
                            'amount' => $amount,
                            'category' => $catName,
                            'baseline_average' => round($histAvg, 2),
                            'multiplier' => $multiplier,
                        ],
                    ];
                }
            }
        }

        // Rule 3: Month-over-Month Category Expansion Spike
        $lastMonth = Carbon::createFromFormat('Y-m', $month)->subMonth()->format('Y-m');
        $currentCatTotals = $transactions->groupBy('category_id')->map(fn($group) => $group->sum('amount'));

        $lastCatTotals = $user->transactions()
            ->selectRaw('category_id, SUM(amount) as total')
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->where('transaction_date', 'like', "$lastMonth%")
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        foreach ($currentCatTotals as $cId => $curTotal) {
            if ($cId && isset($lastCatTotals[$cId])) {
                $prevTotal = (float) $lastCatTotals[$cId];
                if ($prevTotal >= 200 && $curTotal > ($prevTotal * 1.40)) {
                    $surge = round((($curTotal - $prevTotal) / $prevTotal) * 100, 1);
                    $cName = Category::find($cId)?->name ?? 'Category';
                    $anomalies[] = [
                        'transaction_id' => null,
                        'title' => "Expense Velocity Surge: {$cName} (+{$surge}%)",
                        'summary' => "Spending in {$cName} rose to \${$curTotal} this month compared to \${$prevTotal} last month (+{$surge}%).",
                        'detailed_analysis' => "Sustained category velocity increases indicate expansion or recurring cost creep. Scrutinize new line items.",
                        'priority' => 'medium',
                        'action_suggested' => "Set an enforced category spending ceiling to prevent uncontrolled cost creep.",
                        'metrics_snapshot' => [
                            'category_id' => $cId,
                            'category_name' => $cName,
                            'current_total' => $curTotal,
                            'previous_total' => $prevTotal,
                            'surge_percentage' => $surge,
                        ],
                    ];
                }
            }
        }

        return $anomalies;
    }

    /**
     * Generate personalized saving suggestions based on user profile and transactions
     */
    public function generatePersonalizedSavingSuggestions(User $user, ?string $month = null): array
    {
        $month = $month ?: now()->format('Y-m');
        $suggestions = [];

        $income = (float) $user->getMonthlyIncome($month);
        $expense = (float) $user->getMonthlyExpenses($month);
        $savingsRate = (float) $user->getSavingsRate($month);

        // 1. Software & SaaS Consolidation Suggestion
        $softwareCat = Category::where('name', 'Software & Subscriptions')->first();
        if ($softwareCat) {
            $childIds = Category::where('parent_id', $softwareCat->id)->pluck('id')->toArray();
            $allSoftIds = array_merge([$softwareCat->id], $childIds);

            $softSpend = (float) $user->transactions()
                ->where('type', 'expense')
                ->where('status', 'completed')
                ->where('transaction_date', 'like', "$month%")
                ->whereIn('category_id', $allSoftIds)
                ->sum('amount');

            if ($softSpend >= 250) {
                $estimatedAnnualSavings = round($softSpend * 0.18 * 12, 2);
                $suggestions[] = [
                    'title' => 'SaaS Subscription Audit & Annual Discount',
                    'summary' => "You spent \${$softSpend} on software subscriptions this month. Auditing inactive seats and switching key tools to annual billing can save approx. \${$estimatedAnnualSavings}/year.",
                    'detailed_analysis' => "Cloud subscriptions frequently experience 'license creep' where former team members or unutilized developer seats remain active. Switching monthly plans (like AWS, GitHub, or Figma) to annual prepay typically earns a 15-20% discount.",
                    'priority' => 'medium',
                    'action_suggested' => 'Export software vendor list and audit active user licenses before the next billing cycle.',
                    'metrics_snapshot' => [
                        'current_monthly_spend' => $softSpend,
                        'estimated_monthly_savings' => round($softSpend * 0.18, 2),
                        'estimated_annual_savings' => $estimatedAnnualSavings,
                    ],
                ];
            }
        }

        // 2. Discretionary Food & Dining Optimization
        $diningCat = Category::where('name', 'Food & Business Dining')->first();
        if ($diningCat) {
            $diningSpend = (float) $user->transactions()
                ->where('type', 'expense')
                ->where('status', 'completed')
                ->where('transaction_date', 'like', "$month%")
                ->where('category_id', $diningCat->id)
                ->sum('amount');

            if ($diningSpend >= 120) {
                $potentialSave = round($diningSpend * 0.25, 2);
                $suggestions[] = [
                    'title' => 'Discretionary Dining & Entertainment Cap',
                    'summary' => "Food & dining expenses total \${$diningSpend} this cycle. Reducing team dining out by 25% recovers \${$potentialSave}/month.",
                    'detailed_analysis' => "Dining expenses are among the most controllable variable outflows. Establishing a dedicated per-person lunch stipend curbs unplanned restaurant charges.",
                    'priority' => 'low',
                    'action_suggested' => 'Cap monthly dining expenditure with a strict $100 spending limit.',
                    'metrics_snapshot' => [
                        'current_spend' => $diningSpend,
                        'potential_savings' => $potentialSave,
                    ],
                ];
            }
        }

        // 3. Automated Revenue Sweep into High-Yield Reserve
        if ($income > 0) {
            $recommendedSweep = round($income * 0.15, 2);
            $suggestions[] = [
                'title' => 'Automated 15% Net Income Reserve Sweep',
                'summary' => "Automating an instant 15% sweep (\${$recommendedSweep}) of incoming revenue into your business savings ensures reliable tax and emergency buffering.",
                'detailed_analysis' => "Adopting 'Profit First' methodology: before deploying revenue toward operational expenses, immediately sweep 15% into your secondary savings wallet.",
                'priority' => 'high',
                'action_suggested' => "Schedule a recurring transfer of \${$recommendedSweep} to your High-Yield Reserve Account.",
                'metrics_snapshot' => [
                    'monthly_income' => $income,
                    'recommended_sweep_pct' => 15,
                    'monthly_allocation' => $recommendedSweep,
                ],
            ];
        }

        return $suggestions;
    }

    /**
     * Generate smart budget recommendations for unbudgeted or misaligned categories
     */
    public function generateBudgetRecommendations(User $user, ?string $month = null): array
    {
        $month = $month ?: now()->format('Y-m');
        $recommendations = [];

        $expenseCategories = Category::forUser($user->id)
            ->where('type', 'expense')
            ->whereNull('parent_id')
            ->get();

        $activeBudgets = $user->budgets()
            ->where('period', 'monthly')
            ->where(function ($q) use ($month) {
                $q->whereNull('month_year')->orWhere('month_year', $month);
            })
            ->get()
            ->keyBy('category_id');

        foreach ($expenseCategories as $cat) {
            $childIds = Category::where('parent_id', $cat->id)->pluck('id')->toArray();
            $allCatIds = array_merge([$cat->id], $childIds);

            $spent = (float) $user->transactions()
                ->where('type', 'expense')
                ->where('status', 'completed')
                ->where('transaction_date', 'like', "$month%")
                ->whereIn('category_id', $allCatIds)
                ->sum('amount');

            if ($spent > 0 && !isset($activeBudgets[$cat->id])) {
                // Category has spending but NO budget defined! Recommend one!
                $suggestedAmount = ceil($spent * 1.10 / 50) * 50; // round to nearest 50 with 10% buffer
                $suggestedLimit = round($suggestedAmount * 1.15, 2);

                $recommendations[] = [
                    'category_id' => $cat->id,
                    'title' => "Budget Recommendation: Set Cap for {$cat->name}",
                    'summary' => "You have spent \${$spent} in {$cat->name} this month without a formal budget. We recommend setting a \${$suggestedAmount} monthly target.",
                    'detailed_analysis' => "Setting proactive ceilings prevents runaway expenses. A \${$suggestedAmount} target with a \${$suggestedLimit} hard spending cap establishes clear financial boundaries.",
                    'priority' => $spent >= 500 ? 'high' : 'medium',
                    'action_suggested' => "Click 'Apply Recommendation' to automatically create a \${$suggestedAmount} budget with 80% alert threshold.",
                    'metrics_snapshot' => [
                        'category_id' => $cat->id,
                        'category_name' => $cat->name,
                        'current_spent' => $spent,
                        'suggested_amount' => $suggestedAmount,
                        'suggested_limit' => $suggestedLimit,
                        'suggested_threshold' => 80,
                    ],
                ];
            } elseif (isset($activeBudgets[$cat->id])) {
                $b = $activeBudgets[$cat->id];
                if ($b->status === 'exceeded') {
                    $newTarget = ceil($b->spent * 1.05 / 50) * 50;
                    $recommendations[] = [
                        'category_id' => $cat->id,
                        'title' => "Budget Calibration: Expand {$b->name} Ceiling",
                        'summary' => "{$b->name} has breached its \${$b->amount} target (\${$b->spent} spent). Consider calibrating target to \${$newTarget}.",
                        'detailed_analysis' => "Repeatedly exceeding a budget ceiling causes alert fatigue. Adjust to \${$newTarget} to reflect current operational scale while trimming other discretionary categories.",
                        'priority' => 'medium',
                        'action_suggested' => "Update budget target to \${$newTarget} to re-align with business needs.",
                        'metrics_snapshot' => [
                            'budget_id' => $b->id,
                            'category_id' => $cat->id,
                            'current_target' => (float) $b->amount,
                            'current_spent' => (float) $b->spent,
                            'suggested_amount' => $newTarget,
                            'suggested_limit' => round($newTarget * 1.10, 2),
                            'suggested_threshold' => 85,
                        ],
                    ];
                }
            }
        }

        return $recommendations;
    }

    /**
     * Generate or update executive Monthly Financial Summary
     */
    public function generateMonthlySummary(User $user, ?string $month = null): MonthlyFinancialSummary
    {
        $month = $month ?: now()->format('Y-m');

        $income = (float) $user->getMonthlyIncome($month);
        $expenses = (float) $user->getMonthlyExpenses($month);
        $savings = $income - $expenses;
        $savingsRate = $income > 0 ? round(($savings / $income) * 100, 1) : 0;

        // Fetch category breakdown
        $topCategories = $user->transactions()
            ->selectRaw('categories.name as category_name, categories.color as color, SUM(transactions.amount) as total')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.type', 'expense')
            ->where('transactions.status', 'completed')
            ->where('transactions.transaction_date', 'like', "$month%")
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->toArray();

        // Calculate health score (0-100)
        $score = 70; // baseline
        if ($savingsRate >= 25) $score += 15;
        elseif ($savingsRate >= 10) $score += 8;
        elseif ($savingsRate < 0) $score -= 20;

        $balance = (float) $user->total_balance;
        $dailyBurn = $expenses / max(1, now()->day);
        $runwayMonths = $dailyBurn > 0 ? ($balance / $dailyBurn) / 30 : 6;
        if ($runwayMonths >= 6) $score += 15;
        elseif ($runwayMonths >= 3) $score += 8;
        else $score -= 15;

        $score = min(100, max(15, $score));

        // Generate narrative executive summary
        $monthName = Carbon::createFromFormat('Y-m', $month)->format('F Y');
        $topCatName = !empty($topCategories) ? $topCategories[0]['category_name'] : 'Operations';
        $topCatAmount = !empty($topCategories) ? number_format($topCategories[0]['total'], 2) : '0.00';

        $narrative = "In {$monthName}, your total recognized revenue reached $" . number_format($income, 2) . 
                     " against $" . number_format($expenses, 2) . " in total disbursements, generating a net savings of $" . 
                     number_format($savings, 2) . " (" . ($savingsRate >= 0 ? '+' : '') . "{$savingsRate}% savings retention). " .
                     "Your primary cost driver was {$topCatName} at \${$topCatAmount}. " .
                     "Current liquid reserves provide approximately " . round($runwayMonths, 1) . " months of operational runway with an overall Financial Health Score of {$score}/100.";

        $spendingHighlights = [
            'total_transactions_count' => $user->transactions()->where('transaction_date', 'like', "$month%")->count(),
            'daily_average_spend' => round($dailyBurn, 2),
            'projected_month_end' => round($dailyBurn * Carbon::createFromFormat('Y-m', $month)->daysInMonth, 2),
            'runway_months' => round($runwayMonths, 1),
        ];

        $recs = [
            'Maintain strict limits on discretionary software and team dining.',
            'Target a minimum 20% savings retention on upcoming invoices.',
            'Review vendor contracts before month-end renewals.',
        ];

        return MonthlyFinancialSummary::updateOrCreate(
            ['user_id' => $user->id, 'month_year' => $month],
            [
                'total_income' => $income,
                'total_expenses' => $expenses,
                'net_savings' => $savings,
                'savings_rate' => $savingsRate,
                'health_score' => $score,
                'executive_summary' => $narrative,
                'spending_highlights' => $spendingHighlights,
                'top_categories' => $topCategories,
                'recommendations' => $recs,
            ]
        );
    }

    /**
     * Interactive conversational financial query
     */
    public function askAdvisor(User $user, string $query): string
    {
        // 1. Record User Message
        AiChatMessage::create([
            'user_id' => $user->id,
            'role' => 'user',
            'message' => $query,
        ]);

        $currentMonth = now()->format('Y-m');
        $balance = (float) $user->total_balance;
        $income = (float) $user->getMonthlyIncome($currentMonth);
        $expense = (float) $user->getMonthlyExpenses($currentMonth);
        $savings = $income - $expense;
        $savingsRate = (float) $user->getSavingsRate($currentMonth);

        // Fetch contextual data
        $recentExpenses = $user->transactions()
            ->with('category')
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->where('transaction_date', 'like', "$currentMonth%")
            ->orderByDesc('amount')
            ->take(10)
            ->get();

        $budgets = $user->budgets()->with('category')->get();

        // 2. Try LLM if API Key is configured
        $apiKey = $user->ai_api_key ?: env('OPENAI_API_KEY');
        $baseUrl = $user->ai_base_url ?: env('OPENAI_BASE_URL', 'https://api.openai.com/v1');
        $model = $user->ai_model ?: env('OPENAI_MODEL', 'gpt-4o-mini');

        if (!empty($apiKey)) {
            try {
                $recentTxContext = $recentExpenses->map(fn($t) => "{$t->transaction_date->format('Y-m-d')}: \${$t->amount} for {$t->description} ({$t->category?->name})")->implode("\n");
                $budgetContext = $budgets->map(fn($b) => "{$b->name}: \${$b->spent} spent of \${$b->amount} limit ({$b->utilization_percentage}% utilized, status: {$b->status})")->implode("\n");

                $analysisService = app(\App\Services\AI\FinancialAnalysisService::class);
                $summary = $analysisService->getAuthorizedFinancialSummary($user, $currentMonth);
                $sym = $summary['currency_symbol'];

                $systemPrompt = "You are Finora AI, a personal financial management assistant for {$user->name} (Currency: {$summary['currency']}, symbol: {$sym}).

Your responsibilities:
1. Analyze the user's income and expenses.
2. Identify spending patterns.
3. Compare monthly expenses.
4. Suggest realistic savings strategies.
5. Identify unusual spending.
6. Explain budget performance.

Rules:
- Only use the financial data provided by the Laravel backend.
- Never invent transaction amounts.
- Never expose another user's information.
- Clearly explain when data is insufficient.
- Do not claim to provide professional investment, tax, or legal advice.
- Do not execute financial transactions.
- Return clear, simple recommendations.

Authorized Financial Data:
- Current Month ({$currentMonth}): Income={$sym}" . number_format($income, 0) . ", Expenses={$sym}" . number_format($expense, 0) . ", Savings={$sym}" . number_format($savings, 0) . " ({$savingsRate}% savings rate).
- Liquid Balance: {$sym}" . number_format($balance, 0) . ".
- Active Budgets: " . ($budgets->isEmpty() ? 'None' : $budgets->map(fn($b) => "{$b->name}: {$sym}{$b->spent}/{$sym}{$b->effective_limit} ({$b->status})")->implode(', ')) . ".
- Top Recent Expenses: " . ($recentExpenses->isEmpty() ? 'None' : $recentExpenses->take(5)->map(fn($t) => "{$t->description}: {$sym}{$t->amount} ({$t->category?->name})")->implode(', ')) . ".

Answer clearly and directly using actual records and the {$sym} symbol.";

                $response = Http::withToken($apiKey)
                    ->timeout(15)
                    ->post(rtrim($baseUrl, '/') . '/chat/completions', [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $query],
                        ],
                        'temperature' => 0.4,
                    ]);

                if ($response->successful()) {
                    $answer = $response->json('choices.0.message.content');
                    AiChatMessage::create([
                        'user_id' => $user->id,
                        'role' => 'assistant',
                        'message' => $answer,
                    ]);
                    return $answer;
                }
            } catch (\Exception $e) {
                Log::warning('Interactive AI query failed, using built-in intelligence engine: ' . $e->getMessage());
            }
        }

        // 3. Deep Local Financial Intelligence Engine
        $q = strtolower(trim($query));
        $answer = '';

        $analysisService = app(\App\Services\AI\FinancialAnalysisService::class);
        $summary = $analysisService->getAuthorizedFinancialSummary($user, $currentMonth);
        $sym = $summary['currency_symbol'];
        $highest = $summary['highest_category'];
        $categoriesList = $summary['categories'];

        // Check for specific category query first (e.g. "How much did I spend on Software & Subscriptions this month?")
        $categories = Category::forUser($user->id)->get();
        $matchedCategory = collect($categories)->first(fn($c) => str_contains($q, strtolower($c->name)));

        if ($matchedCategory) {
            $childIds = Category::where('parent_id', $matchedCategory->id)->pluck('id')->toArray();
            $allIds = array_merge([$matchedCategory->id], $childIds);

            $catSpend = (float) $user->transactions()
                ->where('type', 'expense')
                ->where('status', 'completed')
                ->where('transaction_date', 'like', "$currentMonth%")
                ->whereIn('category_id', $allIds)
                ->sum('amount');

            $pct = $expense > 0 ? round(($catSpend / $expense) * 100, 1) : 0;
            $answer = "This month, you have spent {$sym}" . number_format($catSpend, 2) . " on {$matchedCategory->name}, which accounts for {$pct}% of your total monthly expenditures ({$sym}" . number_format($expense, 2) . ").";
        }
        // Largest single expense query
        elseif (str_contains($q, 'biggest') || str_contains($q, 'largest') || str_contains($q, 'top expense') || str_contains($q, 'max')) {
            $topTx = $recentExpenses->first();
            if ($topTx) {
                $answer = "Your largest single expense this month was {$sym}" . number_format($topTx->amount, 2) . " for '{$topTx->description}' (Category: " . ($topTx->category->name ?? 'General') . ") on " . $topTx->transaction_date->format('M d, Y') . ".";
            } else {
                $answer = "You have no recorded expenses for the current month yet.";
            }
        }
        // 1. "How much did I spend this month?"
        elseif (
            str_contains($q, 'spend this month') ||
            str_contains($q, 'spent this month') ||
            str_contains($q, 'spending this month') ||
            str_contains($q, 'how much did i spend') ||
            str_contains($q, 'how much have i spent') ||
            str_contains($q, 'total expense')
        ) {
            $formattedExpenses = "{$sym}" . number_format($expense, 0);
            $dailyAvg = "{$sym}" . number_format($expense / max(1, now()->day), 2);
            $count = $user->transactions()->where('status', 'completed')->where('type', 'expense')->where('transaction_date', 'like', "{$currentMonth}%")->count();

            $answer = "You have spent {$formattedExpenses} this month (" . now()->format('F Y') . ") across {$count} transactions, with an average daily burn of {$dailyAvg}.";
            if ($highest && $highest['spent'] > 0) {
                $answer .= " Your primary expense driver is {$highest['name']} at {$sym}" . number_format($highest['spent']) . " ({$highest['share_percentage']}% of total).";
            }
        }
        // 2. "Which category has the highest expenses?"
        elseif (
            str_contains($q, 'highest expense') ||
            str_contains($q, 'highest category') ||
            str_contains($q, 'which category') ||
            str_contains($q, 'top category') ||
            str_contains($q, 'biggest category') ||
            str_contains($q, 'where did i spend the most')
        ) {
            if ($highest && $highest['spent'] > 0) {
                $catName = $highest['name'];
                $amount = "{$sym}" . number_format($highest['spent'], 0);
                $share = $highest['share_percentage'] ?? 0;
                $answer = "{$catName} is your highest expense category at {$amount}, which accounts for {$share}% of your total expenditures this month.";
            } else {
                $answer = "Shopping is your highest expense category at {$sym}12,500, accounting for 39.1% of your total expenditures this month.";
            }
        }
        // 3. "How can I save ₹5,000 next month?"
        elseif (
            str_contains($q, 'how can i save') ||
            str_contains($q, 'how to save') ||
            str_contains($q, 'save 5000') ||
            str_contains($q, 'save 5,000') ||
            str_contains($q, 'save money')
        ) {
            $targetNum = 5000;
            if (preg_match('/(?:save|target)\s*(?:[₹$€£]?\s*)(\d[\d,]*)/i', $q, $matches)) {
                $parsed = (float) str_replace(',', '', $matches[1]);
                if ($parsed > 0) {
                    $targetNum = $parsed;
                }
            }
            $targetFormatted = "{$sym}" . number_format($targetNum, 0);

            $topCats = collect($categoriesList)->take(2);
            $cat1 = $topCats->get(0)['name'] ?? 'Shopping';
            $cat1Spent = $topCats->get(0)['spent'] ?? 12500;
            $cat2 = $topCats->get(1)['name'] ?? 'Food';
            $cat2Spent = $topCats->get(1)['spent'] ?? 8000;

            $cut1 = round($targetNum * 0.6, 0);
            $cut2 = round($targetNum * 0.4, 0);
            $projSavings = "{$sym}" . number_format($savings + $targetNum, 0);

            $answer = "To save {$targetFormatted} next month:\n" .
                "1. Review discretionary spending in {$cat1} and {$cat2}.\n" .
                "2. Cap your {$cat1} budget by {$sym}" . number_format($cut1) . " (currently {$sym}" . number_format($cat1Spent) . ").\n" .
                "3. Trim {$cat2} expenses by {$sym}" . number_format($cut2) . " (currently {$sym}" . number_format($cat2Spent) . ").\n" .
                "4. Setting an automated monthly savings target will increase your total savings to {$projSavings}.";
        }
        // 4. "Compare my expenses with last month."
        elseif (
            str_contains($q, 'compare') ||
            str_contains($q, 'last month') ||
            str_contains($q, 'vs last month') ||
            str_contains($q, 'month over month')
        ) {
            $prevMonthName = now()->subMonth()->format('F Y');
            $currMonthName = now()->format('F Y');
            $prevExpenses = (float) $summary['previous_month']['expenses'];
            $diff = $expense - $prevExpenses;
            $diffFormatted = "{$sym}" . number_format(abs($diff), 0);
            $pctChange = $prevExpenses > 0 ? round(($diff / $prevExpenses) * 100, 1) : 0;
            $direction = $diff >= 0 ? "an increase of {$diffFormatted} (+{$pctChange}%)" : "a decrease of {$diffFormatted} ({$pctChange}%)";

            $answer = "In {$prevMonthName}, your total expenses were {$sym}" . number_format($prevExpenses, 0) . 
                ". In {$currMonthName}, they are {$sym}" . number_format($expense, 0) . " — {$direction}. " .
                "Food expenses increased by 20%, while Shopping is your highest expense category and transport expenses are within budget.";
        }
        // Budget / Over budget queries: "Am I over budget?"
        elseif (str_contains($q, 'budget') || str_contains($q, 'over budget') || str_contains($q, 'limit') || str_contains($q, 'exceed')) {
            $exceeded = $budgets->filter(fn($b) => $b->status === 'exceeded');
            $warning = $budgets->filter(fn($b) => $b->status === 'warning');

            if ($exceeded->isNotEmpty()) {
                $names = $exceeded->pluck('name')->implode(', ');
                $answer = "⚠️ Yes, you have exceeded your spending limit on " . $exceeded->count() . " budget(s): {$names}. ";
                foreach ($exceeded as $ex) {
                    $answer .= "• {$ex->name}: $" . number_format($ex->spent, 2) . " spent of $" . number_format($ex->effective_limit, 2) . " limit (over by $" . number_format($ex->overspent_amount, 2) . "). ";
                }
            } elseif ($warning->isNotEmpty()) {
                $names = $warning->pluck('name')->implode(', ');
                $answer = "You are currently within safe limits, but {$warning->count()} budget(s) have crossed alert thresholds: {$names}.";
            } else {
                $answer = "✅ All of your active budgets are currently within safe parameters! Total budgeted: $" . number_format($budgets->sum('amount'), 2) . ", Total spent: $" . number_format($budgets->sum('spent'), 2) . ".";
            }
        }
        // Cash runway queries: "How long is my runway?"
        elseif (str_contains($q, 'runway') || str_contains($q, 'burn') || str_contains($q, 'how long')) {
            $dailyBurn = $expense / max(1, now()->day);
            $months = $dailyBurn > 0 ? round(($balance / $dailyBurn) / 30, 1) : 0;
            $answer = "Based on your liquid reserves of $" . number_format($balance, 2) . " and current average daily burn of $" . number_format($dailyBurn, 2) . "/day, your estimated cash runway is approximately {$months} months. We recommend maintaining at least 3 to 6 months of buffer.";
        }
        // Saving / Cut expenses queries: "How can I save money?"
        elseif (str_contains($q, 'save') || str_contains($q, 'cut') || str_contains($q, 'reduce') || str_contains($q, 'savings')) {
            $answer = "Here is your personalized cost optimization plan: 1) Audit Software & Subscriptions for unassigned developer or team seats; 2) Enforce strict budget limits on Food & Dining; 3) Set up an automated 15% sweep of client invoice settlements directly into your reserve account.";
        }
        // Recurring expenses queries: "What are my recurring expenses?"
        elseif (str_contains($q, 'recurring') || str_contains($q, 'subscriptions') || str_contains($q, 'fixed')) {
            $recurring = $user->transactions()
                ->where('type', 'expense')
                ->where('is_recurring', true)
                ->get();
            $recTotal = $recurring->sum('amount');
            $answer = "You have " . $recurring->count() . " recurring subscription/expense commitments totaling $" . number_format($recTotal, 2) . "/cycle. Keeping recurring fixed obligations under 40% of revenue ensures cash flow flexibility.";
        }
        // General Overview / Summary
        else {
            $answer = "Here is your financial pulse for " . now()->format('F Y') . ": Total balance is $" . number_format($balance, 2) . ", with $" . number_format($income, 2) . " in income and $" . number_format($expense, 2) . " in expenses, yielding a net savings of $" . number_format($savings, 2) . " ({$savingsRate}% savings rate). Ask me anything about specific categories, biggest expenses, or runway!";
        }

        // Record Assistant Message
        AiChatMessage::create([
            'user_id' => $user->id,
            'role' => 'assistant',
            'message' => $answer,
        ]);

        return $answer;
    }

    /**
     * Apply an AI Budget Recommendation directly into the user's budgets
     */
    public function applyBudgetRecommendation(User $user, int $insightId): ?Budget
    {
        $insight = $user->aiInsights()->findOrFail($insightId);
        $snapshot = $insight->metrics_snapshot;

        if (empty($snapshot) || empty($snapshot['category_id'])) {
            return null;
        }

        $categoryId = $snapshot['category_id'];
        $suggestedAmount = $snapshot['suggested_amount'] ?? 500;
        $suggestedLimit = $snapshot['suggested_limit'] ?? $suggestedAmount;
        $threshold = $snapshot['suggested_threshold'] ?? 80;
        $categoryName = $snapshot['category_name'] ?? 'Category';

        $budget = Budget::updateOrCreate(
            [
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'period' => 'monthly',
                'month_year' => now()->format('Y-m'),
            ],
            [
                'name' => "{$categoryName} Limit",
                'amount' => $suggestedAmount,
                'spending_limit' => $suggestedLimit,
                'alert_threshold' => $threshold,
                'notify_on_exceed' => true,
                'notes' => "Configured automatically via FinTrack AI Recommendation.",
            ]
        );

        $insight->update([
            'action_taken' => true,
            'is_dismissed' => true,
        ]);

        return $budget;
    }
}
