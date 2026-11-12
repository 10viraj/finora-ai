<?php

namespace App\Services;

use App\Models\AiInsight;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiFinancialService
{
    /**
     * Run intelligent financial analysis for a user
     */
    public function analyzeFinances(User $user): array
    {
        $now = Carbon::now();
        $currentMonth = $now->format('Y-m');
        $lastMonth = $now->copy()->subMonth()->format('Y-m');

        // Extract key financial indicators
        $balance = $user->total_balance;
        $currIncome = $user->getMonthlyIncome($currentMonth);
        $currExpense = $user->getMonthlyExpenses($currentMonth);
        $lastExpense = $user->getMonthlyExpenses($lastMonth);

        $expensesByCategory = $user->transactions()
            ->selectRaw('categories.name, SUM(transactions.amount) as total')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.type', 'expense')
            ->where('transactions.transaction_date', 'like', "$currentMonth%")
            ->groupBy('categories.name')
            ->pluck('total', 'name')
            ->toArray();

        $budgets = $user->budgets()->with('category')->get();

        $insightsGenerated = [];

        // Check if OpenAI API key is set
        $apiKey = $user->ai_api_key ?: env('OPENAI_API_KEY');
        $baseUrl = $user->ai_base_url ?: env('OPENAI_BASE_URL', 'https://api.openai.com/v1');
        $model = $user->ai_model ?: env('OPENAI_MODEL', 'gpt-4o-mini');

        if (!empty($apiKey)) {
            try {
                $prompt = "You are a senior financial advisor for {$user->role} ({$user->name}).
Financial summary:
- Total Balance: \${$balance}
- Current Month Income: \${$currIncome}
- Current Month Expense: \${$currExpense}
- Previous Month Expense: \${$lastExpense}
- Expenses by category: " . json_encode($expensesByCategory) . "

Generate 3 concise, highly actionable recommendations in JSON format:
[
  {
    \"type\": \"spending_anomaly|budget_warning|savings_opportunity|cashflow_forecast|business_advice\",
    \"title\": \"Short Title\",
    \"summary\": \"1-2 sentence executive summary\",
    \"detailed_analysis\": \"Deeper explanation with numbers and suggestions\",
    \"priority\": \"low|medium|high\",
    \"action_suggested\": \"Actionable next step\"
  }
]
Return pure JSON only.";

                $response = Http::withToken($apiKey)
                    ->timeout(12)
                    ->post(rtrim($baseUrl, '/') . '/chat/completions', [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => 'You are an expert AI financial advisor and business accountant.'],
                            ['role' => 'user', 'content' => $prompt],
                        ],
                        'temperature' => 0.4,
                    ]);

                if ($response->successful()) {
                    $content = $response->json('choices.0.message.content');
                    $cleaned = preg_replace('/```json|```/', '', $content);
                    $parsed = json_decode(trim($cleaned), true);

                    if (is_array($parsed)) {
                        foreach ($parsed as $item) {
                            $insightsGenerated[] = AiInsight::create([
                                'user_id' => $user->id,
                                'type' => $item['type'] ?? 'savings_opportunity',
                                'title' => $item['title'],
                                'summary' => $item['summary'],
                                'detailed_analysis' => $item['detailed_analysis'] ?? null,
                                'priority' => $item['priority'] ?? 'medium',
                                'action_suggested' => $item['action_suggested'] ?? null,
                            ]);
                        }
                        return $insightsGenerated;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('AI API call failed, falling back to heuristic engine: ' . $e->getMessage());
            }
        }

        // Heuristic analysis engine
        // 1. Budget Warnings
        foreach ($budgets as $budget) {
            if ($budget->utilization_percentage >= 80) {
                $insightsGenerated[] = AiInsight::create([
                    'user_id' => $user->id,
                    'type' => 'budget_warning',
                    'title' => "Budget Threshold Alert: {$budget->name}",
                    'summary' => "You have utilized {$budget->utilization_percentage}% of your {$budget->name} budget (\${$budget->spent} of \${$budget->amount}).",
                    'detailed_analysis' => "Spending velocity in {$budget->name} indicates you may exceed your planned ceiling before the end of the billing cycle.",
                    'priority' => $budget->utilization_percentage >= 100 ? 'high' : 'medium',
                    'action_suggested' => "Review upcoming purchase orders or adjust discretionary category limits.",
                ]);
            }
        }

        // 2. Spending increase check
        if ($lastExpense > 0 && $currExpense > $lastExpense * 1.15) {
            $surge = round((($currExpense - $lastExpense) / $lastExpense) * 100, 1);
            $insightsGenerated[] = AiInsight::create([
                'user_id' => $user->id,
                'type' => 'spending_anomaly',
                'title' => "Expense Velocity Increased by {$surge}%",
                'summary' => "Current month outgoings (\${$currExpense}) are {$surge}% higher than the prior month (\${$lastExpense}).",
                'detailed_analysis' => "The primary drivers are operational expenditures and category expansions. Analyze recent transaction logs for unplanned capital outlays.",
                'priority' => 'high',
                'action_suggested' => "Audit recent vendor invoices to ensure no unexpected recurring charges were billed.",
            ]);
        }

        // 3. Cashflow Runway (Small business value)
        $avgDailyBurn = $currExpense / max(1, $now->day);
        if ($avgDailyBurn > 0) {
            $runwayDays = round($balance / $avgDailyBurn);
            $runwayMonths = round($runwayDays / 30, 1);

            $insightsGenerated[] = AiInsight::create([
                'user_id' => $user->id,
                'type' => 'cashflow_forecast',
                'title' => "Estimated Cash Runway: {$runwayMonths} Months",
                'summary' => "At your current average daily burn rate of \${$avgDailyBurn}/day, your liquid reserves of \${$balance} provide approx. {$runwayMonths} months of operational runway.",
                'detailed_analysis' => "Maintaining a 4 to 6-month buffer protects against delayed client receivables and seasonality in small business cash flows.",
                'priority' => $runwayMonths < 3 ? 'high' : 'low',
                'action_suggested' => $runwayMonths < 3 ? 'Prioritize invoicing overdue receivables and restrict non-essential capital expenses.' : 'Consider deploying surplus idle capital into yield-generating reserve accounts.',
            ]);
        }

        // 4. Savings rate recommendation
        $savingsRate = $user->getSavingsRate($currentMonth);
        if ($savingsRate < 20 && $currIncome > 0) {
            $insightsGenerated[] = AiInsight::create([
                'user_id' => $user->id,
                'type' => 'savings_opportunity',
                'title' => 'Savings Rate Optimization Strategy',
                'summary' => "Your net savings rate for the current month is {$savingsRate}%. Target benchmark for your profile is 25% - 30%.",
                'detailed_analysis' => "Reallocating 5% of monthly subscriptions and non-essential dining expenses would boost your emergency buffer by over \${$currIncome * 0.05} this month.",
                'priority' => 'medium',
                'action_suggested' => 'Set up an automated recurring transfer of 10% of every incoming client invoice into your reserve account.',
            ]);
        }

        return $insightsGenerated;
    }

    /**
     * Interactive financial advisor query
     */
    public function askAdvisor(User $user, string $query): string
    {
        $apiKey = $user->ai_api_key ?: env('OPENAI_API_KEY');
        $baseUrl = $user->ai_base_url ?: env('OPENAI_BASE_URL', 'https://api.openai.com/v1');
        $model = $user->ai_model ?: env('OPENAI_MODEL', 'gpt-4o-mini');

        $currentMonth = now()->format('Y-m');
        $balance = $user->total_balance;
        $income = $user->getMonthlyIncome($currentMonth);
        $expense = $user->getMonthlyExpenses($currentMonth);
        $savings = $user->getMonthlySavings($currentMonth);

        if (!empty($apiKey)) {
            try {
                $response = Http::withToken($apiKey)
                    ->timeout(15)
                    ->post(rtrim($baseUrl, '/') . '/chat/completions', [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => "You are FinTrack AI, a professional financial advisor for {$user->name}. Current balance: \${$balance}, this month's income: \${$income}, expenses: \${$expense}, net savings: \${$savings}. Give clear, data-backed financial advice."],
                            ['role' => 'user', 'content' => $query],
                        ],
                        'temperature' => 0.5,
                    ]);

                if ($response->successful()) {
                    return $response->json('choices.0.message.content');
                }
            } catch (\Exception $e) {
                Log::warning('Interactive AI query failed: ' . $e->getMessage());
            }
        }

        // Heuristic answering
        $q = strtolower($query);
        if (str_contains($q, 'runway') || str_contains($q, 'cashflow') || str_contains($q, 'how long')) {
            $dailyBurn = $expense / max(1, now()->day);
            $months = round(($balance / max(1, $dailyBurn)) / 30, 1);
            return "Based on your current liquid balance of \${$balance} and an average daily burn rate of \${$dailyBurn}/day, your estimated cash runway is approximately {$months} months. We recommend maintaining at least 3-6 months of runway.";
        }

        if (str_contains($q, 'save') || str_contains($q, 'reduce') || str_contains($q, 'cut')) {
            return "To maximize your savings, consider: 1) Auditing software and recurring SaaS subscriptions for inactive user seats; 2) Enforcing strict budget caps with threshold alerts; 3) Automating a 15% sweep of net income directly into your high-yield reserve account immediately upon invoice settlement.";
        }

        return "Here is your current financial pulse: Total balance is \${$balance}, with \${$income} in completed revenue and \${$expense} in outgoings this month, yielding a net savings of \${$savings}. You can connect your OpenAI API key in Settings for fully customized conversational answers!";
    }
}
