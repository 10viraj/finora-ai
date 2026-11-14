<?php

namespace App\Services\AI;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;

class AIChatService
{
    public function __construct(
        protected AIClient $client,
        protected FinancialAnalysisService $analysisService
    ) {}

    /**
     * Handle multi-turn conversation and context-aware responses.
     */
    public function chat(User $user, string $question, ?int $conversationId = null): array
    {
        return $this->ask($user, $question, $conversationId);
    }

    /**
     * Handle multi-turn conversation and context-aware responses.
     */
    public function ask(User $user, string $question, ?int $conversationId = null): array
    {
        $conversation = null;
        if ($conversationId) {
            $conversation = $user->aiConversations()->find($conversationId);
        }

        if (!$conversation) {
            $conversation = $user->aiConversations()->create([
                'title' => substr($question, 0, 45) . (strlen($question) > 45 ? '...' : ''),
                'context_type' => 'financial_chat',
                'status' => 'active',
            ]);
        }

        // Store user turn
        $userMsg = $conversation->messages()->create([
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $question,
            'message' => $question,
        ]);

        // Build authorized financial ledger data summary
        $summary = $this->analysisService->getAuthorizedFinancialSummary($user);
        $sym = $summary['currency_symbol'];
        $curr = $summary['current_month'];
        $prev = $summary['previous_month'];
        $comp = $summary['comparison'];
        $highest = $summary['highest_category'];
        $bRec = $summary['budget_recommendation'];

        $contextPrompt = "You are Finora AI, a personal financial management assistant for {$user->name} (Currency: {$summary['currency']}, symbol: {$sym}).

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

Authorized Financial Data Summary:
- Current Month ({$curr['month_name']}): Income={$sym}" . number_format($curr['income'], 0) . ", Expenses={$sym}" . number_format($curr['expenses'], 0) . ", Savings={$sym}" . number_format($curr['savings'], 0) . " ({$curr['savings_rate']}% savings rate), Transactions Count={$curr['transactions_count']}, Daily Avg Spend={$sym}" . number_format($curr['daily_avg_spend'], 2) . ".
- Previous Month ({$prev['month_name']}): Income={$sym}" . number_format($prev['income'], 0) . ", Expenses={$sym}" . number_format($prev['expenses'], 0) . ".
- Month-over-Month Expense Variance: " . ($comp['expense_difference'] >= 0 ? "+{$sym}" : "-{$sym}") . number_format(abs($comp['expense_difference']), 0) . " ({$comp['expense_pct_change']}%).
- Highest Expense Category: " . ($highest ? "{$highest['name']} at {$sym}" . number_format($highest['spent'], 0) . " ({$highest['share_percentage']}%)" : "None") . ".
- Category Patterns: " . implode(" | ", $summary['spending_analysis']) . ".
- Budget Recommendation: Income: {$bRec['formatted']['income']}, Expenses: {$bRec['formatted']['expenses']}, Savings: {$bRec['formatted']['savings']}, Target: {$bRec['formatted']['target']} ({$bRec['ai_suggestion']}).";

        // Check subscription tier daily usage limits
        $subscriptionService = app(\App\Services\SubscriptionService::class);
        if (!$subscriptionService->canPerformAction($user, 'ai_query')) {
            $planInfo = $subscriptionService->getUserSubscription($user);
            $limitMsg = "Daily AI query limit reached ({$planInfo['daily_ai_limit']} queries/day on {$planInfo['plan_name']}). Please upgrade to Finora Pro for higher capacity and priority processing.";
            return [
                'conversation_id' => $conversation->id,
                'question' => $question,
                'answer' => $limitMsg,
                'tokens_used' => 0,
            ];
        }

        // Record AI query usage
        $subscriptionService->recordAiQueryUsage($user);

        $aiResult = $this->client->query($user, $question, $contextPrompt, $summary);
        $validatedContent = $this->validateAiResponse($aiResult['content'] ?? '', $summary);

        // Store assistant response turn with cost tracking
        $assistantMsg = $conversation->messages()->create([
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => $validatedContent,
            'message' => $validatedContent,
            'token_usage' => $aiResult['tokens_used'] ?? 30,
            'tokens_used' => $aiResult['tokens_used'] ?? 30,
            'tokens_prompt' => $aiResult['tokens_prompt'] ?? 15,
            'tokens_completion' => $aiResult['tokens_completion'] ?? 15,
            'cost_usd' => $aiResult['cost_usd'] ?? 0.000000,
            'cost_inr' => $aiResult['cost_inr'] ?? 0.0000,
        ]);

        return [
            'conversation_id' => $conversation->id,
            'question' => $question,
            'answer' => $validatedContent,
            'tokens_used' => $assistantMsg->tokens_used,
            'cost_usd' => $assistantMsg->cost_usd,
            'cost_inr' => $assistantMsg->cost_inr,
        ];
    }

    /**
     * Validate and sanitize AI response
     */
    protected function validateAiResponse(string $content, array $summary): string
    {
        $sanitized = trim($content);
        if (empty($sanitized)) {
            $sym = $summary['currency_symbol'] ?? '₹';
            $exp = $summary['current_month']['expenses'] ?? 0;
            return "Based on your authorized financial records for this month, you have spent {$sym}" . number_format($exp, 0) . ". If you have more transactions to record, please log them to enrich your spending analysis.";
        }

        // Sanitize unexpected HTML while preserving newlines
        return strip_tags($sanitized);
    }
}

