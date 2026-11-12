<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedMonth = $request->input('month', now()->format('Y-m'));

        $budgets = $user->budgets()
            ->with('category')
            ->where(function ($q) use ($selectedMonth) {
                $q->whereNull('month_year')->orWhere('month_year', $selectedMonth);
            })
            ->get();

        $categories = Category::forUser($user->id)->where('type', 'expense')->get();

        $totalBudgeted = $budgets->sum('amount');
        $totalSpent = $budgets->sum(fn($b) => $b->spent);
        $totalRemaining = $totalBudgeted - $totalSpent;
        $overallPercentage = $totalBudgeted > 0 ? round(($totalSpent / $totalBudgeted) * 100, 1) : 0;

        return view('budgets.index', compact(
            'budgets',
            'categories',
            'selectedMonth',
            'totalBudgeted',
            'totalSpent',
            'totalRemaining',
            'overallPercentage'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'month_year' => ['nullable', 'string', 'max:7'],
            'alert_threshold' => ['required', 'integer', 'min:10', 'max:100'],
        ]);

        $user->budgets()->create([
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'amount' => $validated['amount'],
            'period' => 'monthly',
            'month_year' => $validated['month_year'] ?: now()->format('Y-m'),
            'alert_threshold' => $validated['alert_threshold'],
            'notify_on_exceed' => true,
        ]);

        return back()->with('success', 'Budget created successfully.');
    }

    public function destroy(Budget $budget)
    {
        if ($budget->user_id !== Auth::id()) {
            abort(403);
        }

        $budget->delete();

        return back()->with('success', 'Budget removed.');
    }
}
