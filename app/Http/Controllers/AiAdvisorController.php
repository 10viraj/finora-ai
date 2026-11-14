<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Models\MonthlyFinancialSummary;
use App\Services\AiFinancialService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiAdvisorController extends Controller
{
    public function index(Request $request, AiFinancialService $aiService)
    {
        $user = Auth::user();
        $now = Carbon::now();
        $activeTab = $request->input('tab', 'overview');
        $selectedMonth = $request->input('month', $now->format('Y-m'));

        // All active insights
        $allInsights = $user->aiInsights()->where('is_dismissed', false)->get();

        // Categorized insights
        $anomalies = $allInsights->where('type', 'spending_anomaly');
        $savingSuggestions = $allInsights->where('type', 'savings_opportunity');
        $budgetRecommendations = $allInsights->where('type', 'budget_recommendation');
        $budgetWarnings = $allInsights->where('type', 'budget_warning');
        $cashflowForecasts = $allInsights->where('type', 'cashflow_forecast');

        // Dismissed insights for audit log
        $dismissedInsights = $user->aiInsights()->where('is_dismissed', true)->take(5)->get();

        // Monthly Financial Summary
        $monthlySummary = MonthlyFinancialSummary::where('user_id', $user->id)
            ->where('month_year', $selectedMonth)
            ->first();

        // If no summary exists yet for this month, generate one automatically
        if (!$monthlySummary) {
            $monthlySummary = $aiService->generateMonthlySummary($user, $selectedMonth);
        }

        // Available historical summary months
        $availableMonths = MonthlyFinancialSummary::where('user_id', $user->id)
            ->orderByDesc('month_year')
            ->pluck('month_year')
            ->toArray();

        if (!in_array($now->format('Y-m'), $availableMonths)) {
            array_unshift($availableMonths, $now->format('Y-m'));
        }

        // Chat conversation history (last 20 messages)
        $chatMessages = $user->aiChatMessages()->take(20)->get();

        // Financial Analysis & AI Summaries
        $analysisService = app(\App\Services\AI\FinancialAnalysisService::class);
        $aiSummary = $analysisService->getAuthorizedFinancialSummary($user, $selectedMonth);

        // Quick metrics for top stat pills
        $balance = (float) $user->total_balance;
        $monthlyIncome = (float) $user->getMonthlyIncome($selectedMonth);
        $monthlyExpenses = (float) $user->getMonthlyExpenses($selectedMonth);
        $dailyBurn = $monthlyExpenses / max(1, $now->day);
        $runwayMonths = $dailyBurn > 0 ? round(($balance / $dailyBurn) / 30, 1) : 0;
        $savingsRate = (float) $user->getSavingsRate($selectedMonth);

        return view('ai.index', compact(
            'user',
            'activeTab',
            'selectedMonth',
            'allInsights',
            'anomalies',
            'savingSuggestions',
            'budgetRecommendations',
            'budgetWarnings',
            'cashflowForecasts',
            'dismissedInsights',
            'monthlySummary',
            'availableMonths',
            'chatMessages',
            'balance',
            'monthlyIncome',
            'monthlyExpenses',
            'runwayMonths',
            'savingsRate',
            'aiSummary'
        ));
    }

    public function generate(AiFinancialService $aiService)
    {
        $user = Auth::user();
        $aiService->analyzeFinances($user);

        return back()->with('success', 'AI financial audit completed! Spending analysis, anomalies, saving suggestions, and monthly summary refreshed.');
    }

    public function ask(Request $request, AiFinancialService $aiService)
    {
        $request->validate([
            'query' => ['required', 'string', 'max:500'],
        ]);

        $user = Auth::user();
        $answer = $aiService->askAdvisor($user, $request->input('query'));

        return response()->json([
            'success' => true,
            'question' => $request->input('query'),
            'answer' => $answer,
        ]);
    }

    public function applyRecommendation(Request $request, AiInsight $insight, AiFinancialService $aiService)
    {
        if ($insight->user_id !== Auth::id()) {
            abort(403);
        }

        $budget = $aiService->applyBudgetRecommendation(Auth::user(), $insight->id);

        if ($budget) {
            return redirect()->route('budgets.index')
                ->with('success', "Budget '{$budget->name}' successfully established via AI Recommendation with spending limit $" . number_format($budget->effective_limit, 2) . ".");
        }

        return back()->with('error', 'Unable to apply budget recommendation.');
    }

    public function dismiss(AiInsight $insight)
    {
        if ($insight->user_id !== Auth::id()) {
            abort(403);
        }

        $insight->update(['is_dismissed' => true]);

        return back()->with('success', 'Insight marked as resolved.');
    }

    public function clearChat()
    {
        Auth::user()->aiChatMessages()->delete();

        return back()->with('success', 'AI chat history cleared.');
    }

    public function downloadSummary(Request $request, AiFinancialService $aiService): StreamedResponse
    {
        $user = Auth::user();
        $month = $request->input('month', now()->format('Y-m'));
        $summary = MonthlyFinancialSummary::where('user_id', $user->id)->where('month_year', $month)->first();

        if (!$summary) {
            $summary = $aiService->generateMonthlySummary($user, $month);
        }

        $filename = "FinTrack_AI_Summary_{$month}.txt";

        return response()->streamDownload(function () use ($summary, $user) {
            echo "=================================================================\n";
            echo " FINTRACK AI - MONTHLY FINANCIAL SUMMARY\n";
            echo " Cycle: {$summary->formatted_month} | Account: {$user->name}\n";
            echo "=================================================================\n\n";

            echo "1. FINANCIAL HEALTH SCORE: {$summary->health_score} / 100\n\n";

            echo "2. CASH FLOW HIGHLIGHTS:\n";
            echo "   - Total Revenue / Income:  $" . number_format($summary->total_income, 2) . "\n";
            echo "   - Total Outflows / Expense: $" . number_format($summary->total_expenses, 2) . "\n";
            echo "   - Net Savings:             $" . number_format($summary->net_savings, 2) . "\n";
            echo "   - Savings Retention Rate:   {$summary->savings_rate}%\n\n";

            echo "3. EXECUTIVE NARRATIVE:\n";
            echo "   " . wordwrap($summary->executive_summary, 70, "\n   ") . "\n\n";

            if (!empty($summary->top_categories)) {
                echo "4. TOP EXPENSE DRIVERS:\n";
                foreach ($summary->top_categories as $cat) {
                    echo "   - " . str_pad($cat['category_name'], 30) . ": $" . number_format($cat['total'], 2) . "\n";
                }
                echo "\n";
            }

            if (!empty($summary->recommendations)) {
                echo "5. STRATEGIC RECOMMENDATIONS:\n";
                foreach ($summary->recommendations as $rec) {
                    echo "   • {$rec}\n";
                }
                echo "\n";
            }

            echo "Generated by FinTrack AI Copilot on " . now()->format('Y-m-d H:i:s') . "\n";
        }, $filename, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
