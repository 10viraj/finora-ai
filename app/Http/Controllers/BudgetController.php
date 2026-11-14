<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetRequest;
use App\Models\Budget;
use App\Models\Category;
use App\Services\BudgetService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BudgetController extends Controller
{
    protected BudgetService $budgetService;

    public function __construct(BudgetService $budgetService)
    {
        $this->budgetService = $budgetService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now();

        $period = $request->input('period', 'monthly');
        if (!in_array($period, ['monthly', 'yearly'])) {
            $period = 'monthly';
        }

        $selectedMonth = $request->input('month', $now->format('Y-m'));
        $selectedYear = $request->input('year', $now->format('Y'));
        $timeframe = $period === 'yearly' ? $selectedYear : $selectedMonth;

        $activeTab = $request->input('tab', 'overview');
        $statusFilter = $request->input('status', 'all');

        // Retrieve budgets for period
        $allBudgets = $this->budgetService->getBudgetsForPeriod($user, $period, $timeframe);

        // Filter by status if requested
        $budgets = $allBudgets;
        if ($statusFilter && $statusFilter !== 'all') {
            $budgets = $allBudgets->filter(fn($b) => $b->status === $statusFilter);
        }

        // Summary KPI Metrics
        $summary = $this->budgetService->getSummaryMetrics($allBudgets);

        // Active Alerts
        $activeAlerts = $this->budgetService->getActiveAlerts($allBudgets);

        // Category-wise Breakdown & Budget vs Actual Comparison
        $categoryComparison = $this->budgetService->getCategoryWiseComparison($user, $period, $timeframe);

        // Chart Data for Dual Bar Chart
        $chartData = $this->budgetService->getChartData($categoryComparison);

        // Categories available for new budgets
        $categories = Category::forUser($user->id)
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();

        // Available years for filter dropdown
        $availableYears = [
            $now->year - 1,
            $now->year,
            $now->year + 1,
        ];

        return view('budgets.index', compact(
            'budgets',
            'allBudgets',
            'categories',
            'period',
            'selectedMonth',
            'selectedYear',
            'timeframe',
            'activeTab',
            'statusFilter',
            'summary',
            'activeAlerts',
            'categoryComparison',
            'chartData',
            'availableYears'
        ));
    }

    public function store(StoreBudgetRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();

        $period = $validated['period'];
        $monthYear = $validated['month_year'];

        if (empty($monthYear)) {
            $monthYear = $period === 'yearly' ? now()->format('Y') : now()->format('Y-m');
        } elseif ($period === 'yearly' && strlen($monthYear) > 4) {
            $monthYear = substr($monthYear, 0, 4);
        }

        $spendingLimit = !empty($validated['spending_limit']) ? $validated['spending_limit'] : $validated['amount'];

        $budget = $user->budgets()->create([
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'amount' => $validated['amount'],
            'spending_limit' => $spendingLimit,
            'period' => $period,
            'month_year' => $monthYear,
            'alert_threshold' => $validated['alert_threshold'] ?? $validated['alert_percentage'] ?? 80,
            'alert_percentage' => $validated['alert_percentage'] ?? $validated['alert_threshold'] ?? 80,
            'notify_on_exceed' => (bool) ($request->input('notify_on_exceed', true)),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('budgets.index', [
            'period' => $period,
            'month' => $period === 'monthly' ? $monthYear : now()->format('Y-m'),
            'year' => $period === 'yearly' ? $monthYear : now()->format('Y'),
        ])->with('success', "Budget '{$budget->name}' created successfully with spending limit $" . number_format($spendingLimit, 2) . ".");
    }

    public function edit(Budget $budget)
    {
        if ($budget->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json([
            'success' => true,
            'budget' => $budget,
        ]);
    }

    public function update(Request $request, Budget $budget)
    {
        if ($budget->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'spending_limit' => ['nullable', 'numeric', 'min:1'],
            'period' => ['required', 'in:monthly,yearly'],
            'month_year' => ['nullable', 'string', 'max:7'],
            'alert_threshold' => ['required', 'integer', 'min:10', 'max:100'],
            'notify_on_exceed' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $period = $validated['period'];
        $monthYear = $validated['month_year'];

        if (empty($monthYear)) {
            $monthYear = $period === 'yearly' ? now()->format('Y') : now()->format('Y-m');
        } elseif ($period === 'yearly' && strlen($monthYear) > 4) {
            $monthYear = substr($monthYear, 0, 4);
        }

        $spendingLimit = !empty($validated['spending_limit']) ? $validated['spending_limit'] : $validated['amount'];

        $budget->update([
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'amount' => $validated['amount'],
            'spending_limit' => $spendingLimit,
            'period' => $period,
            'month_year' => $monthYear,
            'alert_threshold' => $validated['alert_threshold'] ?? $validated['alert_percentage'] ?? 80,
            'alert_percentage' => $validated['alert_percentage'] ?? $validated['alert_threshold'] ?? 80,
            'notify_on_exceed' => (bool) ($request->input('notify_on_exceed', true)),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('budgets.index', [
            'period' => $period,
            'month' => $period === 'monthly' ? $monthYear : now()->format('Y-m'),
            'year' => $period === 'yearly' ? $monthYear : now()->format('Y'),
        ])->with('success', "Budget '{$budget->name}' updated successfully.");
    }

    public function destroy(Budget $budget)
    {
        if ($budget->user_id !== Auth::id()) {
            abort(403);
        }

        $name = $budget->name;
        $budget->delete();

        return back()->with('success', "Budget '{$name}' removed.");
    }

    /**
     * Export Budget vs Actual Comparison as CSV
     */
    public function exportComparison(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $now = Carbon::now();

        $period = $request->input('period', 'monthly');
        $timeframe = $period === 'yearly' ? $request->input('year', $now->format('Y')) : $request->input('month', $now->format('Y-m'));

        $comparison = $this->budgetService->getCategoryWiseComparison($user, $period, $timeframe);

        $filename = "budget_vs_actual_{$period}_{$timeframe}.csv";

        return response()->streamDownload(function () use ($comparison, $period, $timeframe) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Category',
                'Period Type',
                'Timeframe',
                'Allocated Budget ($)',
                'Spending Limit ($)',
                'Actual Spent ($)',
                'Variance ($)',
                'Variance (%)',
                'Status',
            ]);

            foreach ($comparison as $row) {
                fputcsv($handle, [
                    $row['category_name'],
                    ucfirst($period),
                    $timeframe,
                    number_format($row['budget_amount'], 2, '.', ''),
                    number_format($row['spending_limit'], 2, '.', ''),
                    number_format($row['spent'], 2, '.', ''),
                    number_format($row['variance_amount'], 2, '.', ''),
                    $row['variance_percentage'] !== null ? $row['variance_percentage'] . '%' : 'N/A',
                    ucfirst($row['status']),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
