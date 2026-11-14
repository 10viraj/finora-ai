<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\MonthlyFinancialSummary;
use App\Services\AI\AIChatService;
use App\Services\AI\FinancialAnalysisService;
use App\Services\AI\InsightGeneratorService;
use App\Services\AiFinancialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiApiController extends Controller
{
    protected AiFinancialService $aiFinancialService;
    protected AIChatService $chatService;
    protected FinancialAnalysisService $analysisService;
    protected InsightGeneratorService $insightGenerator;

    public function __construct(
        AiFinancialService $aiFinancialService,
        AIChatService $chatService,
        FinancialAnalysisService $analysisService,
        InsightGeneratorService $insightGenerator
    ) {
        $this->aiFinancialService = $aiFinancialService;
        $this->chatService = $chatService;
        $this->analysisService = $analysisService;
        $this->insightGenerator = $insightGenerator;
    }

    /**
     * Ask AI a question (multi-turn conversation with memory and context).
     */
    public function chat(Request $request): JsonResponse
    {
        $query = $request->input('query') ?: $request->input('question') ?: $request->input('message');

        if (!$query || !is_string($query)) {
            return response()->json([
                'message' => 'The question, query, or message field is required.',
                'errors' => [
                    'query' => ['The query or question field is required.'],
                ],
            ], 422);
        }

        $user = $request->user();
        $conversationId = $request->input('conversation_id');

        $response = $this->chatService->ask($user, $query, $conversationId);

        return response()->json([
            'success' => true,
            'conversation_id' => $response['conversation_id'],
            'question' => $response['question'],
            'answer' => $response['answer'],
            'tokens_used' => $response['tokens_used'],
        ]);
    }

    /**
     * List user AI conversations with summary counts.
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = $user->aiConversations()
            ->withCount('messages')
            ->with(['messages' => function ($q) {
                $q->latest()->take(1);
            }])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $conversations,
        ]);
    }

    /**
     * View generated financial insights.
     */
    public function insights(Request $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->input('type') ?: $request->input('insight_type');

        $query = $user->aiInsights()->where('is_dismissed', false);

        if ($type) {
            $query->where(function ($q) use ($type) {
                $q->where('type', $type)->orWhere('insight_type', $type);
            });
        }

        $insights = $query->latest('generated_at')->get();

        return response()->json([
            'success' => true,
            'count' => $insights->count(),
            'insights' => $insights,
        ]);
    }

    /**
     * Generate financial analysis, health scores, and fresh anomaly insights.
     */
    public function analyze(Request $request): JsonResponse
    {
        $user = $request->user();
        $month = $request->input('month', now()->format('Y-m'));

        $healthScore = $this->analysisService->computeHealthScore($user, $month);
        $snapshot = $this->analysisService->getMonthlySnapshot($user, $month);
        $generatedInsights = $this->insightGenerator->generateInsights($user);

        return response()->json([
            'success' => true,
            'message' => 'Financial analysis completed successfully',
            'health_score' => $healthScore,
            'snapshot' => $snapshot,
            'insights_count' => count($generatedInsights),
            'insights' => $generatedInsights,
        ]);
    }

    /**
     * View personalized saving recommendations and suggestions.
     */
    public function recommendations(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $user->aiRecommendations();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', '!=', 'dismissed');
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $recommendations = $query->latest()->get();
        $potentialSavings = (float) $recommendations->where('status', 'pending')->sum('potential_savings');

        return response()->json([
            'success' => true,
            'total_potential_savings' => $potentialSavings,
            'currency' => $user->currency ?? 'INR',
            'count' => $recommendations->count(),
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * Generate financial summary for a month.
     */
    public function summarize(Request $request): JsonResponse
    {
        $user = $request->user();
        $month = $request->input('month', now()->format('Y-m'));

        $summary = MonthlyFinancialSummary::where('user_id', $user->id)
            ->where('month_year', $month)
            ->first();

        if (!$summary) {
            $summary = $this->aiFinancialService->generateMonthlySummary($user, $month);
        }

        return response()->json([
            'success' => true,
            'month' => $month,
            'summary' => $summary,
        ]);
    }

    /**
     * Backward-compatible alias for ask.
     */
    public function ask(Request $request): JsonResponse
    {
        return $this->chat($request);
    }

    /**
     * Backward-compatible alias for generate.
     */
    public function generate(Request $request): JsonResponse
    {
        return $this->analyze($request);
    }

    /**
     * Backward-compatible alias for summary.
     */
    public function summary(Request $request): JsonResponse
    {
        return $this->summarize($request);
    }
}
