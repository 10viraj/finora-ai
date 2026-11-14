<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Category;
use App\Services\BudgetService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BudgetApiController extends Controller
{
    protected BudgetService $budgetService;

    public function __construct(BudgetService $budgetService)
    {
        $this->budgetService = $budgetService;
    }

    /**
     * List user budgets with status, spent, and alert metrics.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $period = $request->input('period', 'monthly');
        $timeframe = $period === 'yearly' ? $request->input('year', now()->format('Y')) : $request->input('month', now()->format('Y-m'));

        $budgets = $this->budgetService->getBudgetsForPeriod($user, $period, $timeframe);
        $summary = $this->budgetService->getSummaryMetrics($budgets);
        $alerts = $this->budgetService->getActiveAlerts($budgets);

        return response()->json([
            'success' => true,
            'period' => $period,
            'timeframe' => $timeframe,
            'summary' => $summary,
            'alerts_count' => count($alerts),
            'alerts' => $alerts,
            'budgets' => $budgets->map(function ($b) {
                return [
                    'id' => $b->id,
                    'name' => $b->name,
                    'category_id' => $b->category_id,
                    'category_name' => $b->category->name ?? 'General Spending',
                    'amount' => (float) $b->amount,
                    'spending_limit' => (float) $b->effective_limit,
                    'spent' => (float) $b->spent,
                    'remaining' => (float) $b->remaining,
                    'utilization_percentage' => (float) $b->utilization_percentage,
                    'status' => $b->status,
                    'period' => $b->period,
                    'month_year' => $b->month_year,
                    'alert_threshold' => $b->alert_threshold,
                    'alert_percentage' => $b->alert_percentage ?? $b->alert_threshold,
                    'daily_budget' => $b->daily_budget,
                    'days_remaining' => $b->days_remaining,
                    'variance_amount' => $b->variance_amount,
                ];
            }),
        ]);
    }

    /**
     * Create a new budget.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'spending_limit' => ['nullable', 'numeric', 'min:1'],
            'period' => ['required', 'in:monthly,yearly'],
            'month_year' => ['nullable', 'string', 'max:7'],
            'alert_threshold' => ['nullable', 'integer', 'min:10', 'max:100'],
            'alert_percentage' => ['nullable', 'integer', 'min:10', 'max:100'],
            'notify_on_exceed' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $threshold = $validated['alert_percentage'] ?? $validated['alert_threshold'] ?? 80;
        $period = $validated['period'];
        $monthYear = $validated['month_year'] ?? ($period === 'yearly' ? now()->format('Y') : now()->format('Y-m'));

        $budget = $user->budgets()->create([
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'amount' => $validated['amount'],
            'spending_limit' => !empty($validated['spending_limit']) ? $validated['spending_limit'] : $validated['amount'],
            'period' => $period,
            'month_year' => $monthYear,
            'alert_threshold' => $threshold,
            'alert_percentage' => $threshold,
            'notify_on_exceed' => $validated['notify_on_exceed'] ?? true,
            'notes' => $validated['notes'] ?? null,
            'currency' => $user->currency ?? 'INR',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Budget created successfully',
            'budget' => $budget->load('category'),
        ], 201);
    }

    /**
     * View budget details.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $budget = Budget::withoutGlobalScopes()->findOrFail($id);

        Gate::authorize('view', $budget);

        return response()->json([
            'success' => true,
            'budget' => $budget->load(['category', 'budgetCategories.category']),
        ]);
    }

    /**
     * Update an existing budget.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $budget = Budget::withoutGlobalScopes()->findOrFail($id);

        Gate::authorize('update', $budget);

        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:1'],
            'spending_limit' => ['nullable', 'numeric', 'min:1'],
            'period' => ['sometimes', 'required', 'in:monthly,yearly'],
            'month_year' => ['nullable', 'string', 'max:7'],
            'alert_threshold' => ['nullable', 'integer', 'min:10', 'max:100'],
            'alert_percentage' => ['nullable', 'integer', 'min:10', 'max:100'],
            'notify_on_exceed' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (isset($validated['alert_percentage']) && !isset($validated['alert_threshold'])) {
            $validated['alert_threshold'] = $validated['alert_percentage'];
        }

        $budget->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Budget updated successfully',
            'budget' => $budget->fresh()->load('category'),
        ]);
    }

    /**
     * Delete an existing budget (soft delete).
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $budget = Budget::withoutGlobalScopes()->findOrFail($id);

        Gate::authorize('delete', $budget);

        $budget->delete();

        return response()->json([
            'success' => true,
            'message' => 'Budget deleted successfully',
        ]);
    }

    /**
     * Retrieve budget progress metrics for a given budget or general progress.
     */
    public function progress(Request $request, $id = null): JsonResponse
    {
        $user = $request->user();

        if ($id) {
            $budget = Budget::withoutGlobalScopes()->findOrFail($id);
            Gate::authorize('view', $budget);

            return response()->json([
                'success' => true,
                'data' => [
                    'budget_id' => $budget->id,
                    'name' => $budget->name,
                    'category' => $budget->category?->name ?? 'General Spending',
                    'amount' => (float) $budget->amount,
                    'spending_limit' => (float) $budget->effective_limit,
                    'spent' => (float) $budget->spent,
                    'remaining' => (float) $budget->remaining,
                    'utilization_percentage' => (float) $budget->utilization_percentage,
                    'status' => $budget->status,
                    'alert_percentage' => $budget->alert_percentage ?? $budget->alert_threshold,
                    'daily_budget' => $budget->daily_budget,
                    'days_remaining' => $budget->days_remaining,
                    'variance_amount' => $budget->variance_amount,
                ],
            ]);
        }

        // General overall progress across all active budgets
        $period = $request->input('period', 'monthly');
        $timeframe = $period === 'yearly' ? $request->input('year', now()->format('Y')) : $request->input('month', now()->format('Y-m'));

        $budgets = $this->budgetService->getBudgetsForPeriod($user, $period, $timeframe);
        $summary = $this->budgetService->getSummaryMetrics($budgets);

        return response()->json([
            'success' => true,
            'period' => $period,
            'timeframe' => $timeframe,
            'summary' => $summary,
            'budgets' => $budgets->map(function ($b) {
                return [
                    'budget_id' => $b->id,
                    'name' => $b->name,
                    'category' => $b->category?->name ?? 'General Spending',
                    'amount' => (float) $b->amount,
                    'spent' => (float) $b->spent,
                    'remaining' => (float) $b->remaining,
                    'utilization_percentage' => (float) $b->utilization_percentage,
                    'status' => $b->status,
                ];
            }),
        ]);
    }

    /**
     * Budget vs Actual comparison.
     */
    public function comparison(Request $request): JsonResponse
    {
        $user = $request->user();
        $period = $request->input('period', 'monthly');
        $timeframe = $period === 'yearly' ? $request->input('year', now()->format('Y')) : $request->input('month', now()->format('Y-m'));

        $comparison = $this->budgetService->getCategoryWiseComparison($user, $period, $timeframe);
        $chartData = $this->budgetService->getChartData($comparison);

        return response()->json([
            'success' => true,
            'period' => $period,
            'timeframe' => $timeframe,
            'comparison' => $comparison,
            'chart_data' => $chartData,
        ]);
    }

    /**
     * Active budget alerts.
     */
    public function alerts(Request $request): JsonResponse
    {
        $user = $request->user();
        $period = $request->input('period', 'monthly');
        $timeframe = $period === 'yearly' ? $request->input('year', now()->format('Y')) : $request->input('month', now()->format('Y-m'));

        $budgets = $this->budgetService->getBudgetsForPeriod($user, $period, $timeframe);
        $alerts = $this->budgetService->getActiveAlerts($budgets);

        return response()->json([
            'success' => true,
            'alerts_count' => count($alerts),
            'alerts' => $alerts,
        ]);
    }
}
