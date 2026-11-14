<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FinancialGoal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinancialGoalController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $goals = $user->goals()->latest()->get();
        $totalTarget = $goals->sum('target_amount');
        $totalSaved = $goals->sum('current_amount');
        $overallProgress = $totalTarget > 0 ? round(($totalSaved / $totalTarget) * 100, 1) : 0;

        return view('goals.index', compact('goals', 'totalTarget', 'totalSaved', 'overallProgress'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'target_amount' => ['required', 'numeric', 'min:1'],
            'current_amount' => ['nullable', 'numeric', 'min:0'],
            'target_date' => ['nullable', 'date'],
            'category' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $goal = Auth::user()->goals()->create([
            'name' => $validated['name'],
            'target_amount' => $validated['target_amount'],
            'current_amount' => $validated['current_amount'] ?? 0,
            'target_date' => $validated['target_date'] ?? null,
            'category' => $validated['category'] ?? 'savings',
            'notes' => $validated['notes'] ?? null,
            'status' => 'in_progress',
        ]);

        AuditLog::record('goal.created', $goal, null, $goal->toArray());

        return back()->with('success', "Financial goal '{$goal->name}' created successfully.");
    }

    public function deposit(Request $request, FinancialGoal $goal)
    {
        if ($goal->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $oldAmount = $goal->current_amount;
        $newAmount = $oldAmount + $validated['amount'];
        $status = $newAmount >= $goal->target_amount ? 'completed' : 'in_progress';

        $goal->update([
            'current_amount' => $newAmount,
            'status' => $status,
        ]);

        AuditLog::record('goal.deposit', $goal, ['current_amount' => $oldAmount], ['current_amount' => $newAmount]);

        return back()->with('success', "Added " . number_format($validated['amount'], 2) . " towards '{$goal->name}'.");
    }

    public function destroy(FinancialGoal $goal)
    {
        if ($goal->user_id !== Auth::id()) {
            abort(403);
        }

        $oldValues = $goal->toArray();
        $goal->delete();

        AuditLog::record('goal.deleted', $goal, $oldValues, null);

        return back()->with('success', 'Financial goal deleted.');
    }
}

