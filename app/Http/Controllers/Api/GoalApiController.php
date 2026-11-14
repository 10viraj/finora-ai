<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FinancialGoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoalApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $goals = $request->user()->goals()->latest()->get();
        $totalTarget = (float) $goals->sum('target_amount');
        $totalSaved = (float) $goals->sum('current_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'goals' => $goals,
                'total_target' => $totalTarget,
                'total_saved' => $totalSaved,
                'overall_progress_percentage' => $totalTarget > 0 ? round(($totalSaved / $totalTarget) * 100, 1) : 0,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'target_amount' => ['required', 'numeric', 'min:1'],
            'current_amount' => ['nullable', 'numeric', 'min:0'],
            'target_date' => ['nullable', 'date'],
            'category' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $goal = $request->user()->goals()->create([
            'name' => $validated['name'],
            'target_amount' => $validated['target_amount'],
            'current_amount' => $validated['current_amount'] ?? 0,
            'target_date' => $validated['target_date'] ?? null,
            'category' => $validated['category'] ?? 'savings',
            'notes' => $validated['notes'] ?? null,
            'status' => 'in_progress',
        ]);

        AuditLog::record('goal.created', $goal, null, $goal->toArray(), $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Financial goal created successfully.',
            'data' => $goal,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $goal = $request->user()->goals()->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $goal,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $goal = $request->user()->goals()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'target_amount' => ['sometimes', 'numeric', 'min:1'],
            'current_amount' => ['sometimes', 'numeric', 'min:0'],
            'target_date' => ['nullable', 'date'],
            'category' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:in_progress,completed,paused'],
        ]);

        $oldValues = $goal->toArray();
        $goal->update($validated);
        AuditLog::record('goal.updated', $goal, $oldValues, $goal->fresh()->toArray(), $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Financial goal updated successfully.',
            'data' => $goal,
        ]);
    }

    public function deposit(Request $request, int $id): JsonResponse
    {
        $goal = $request->user()->goals()->findOrFail($id);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $oldAmount = (float) $goal->current_amount;
        $newAmount = $oldAmount + (float) $validated['amount'];
        $status = $newAmount >= (float) $goal->target_amount ? 'completed' : 'in_progress';

        $goal->update([
            'current_amount' => $newAmount,
            'status' => $status,
        ]);

        AuditLog::record('goal.deposit', $goal, ['current_amount' => $oldAmount], ['current_amount' => $newAmount], $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Funds allocated to goal.',
            'data' => [
                'goal_id' => $goal->id,
                'deposited' => (float) $validated['amount'],
                'current_amount' => $newAmount,
                'target_amount' => (float) $goal->target_amount,
                'progress_percentage' => $goal->progress_percentage,
                'status' => $goal->status,
            ],
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $goal = $request->user()->goals()->findOrFail($id);
        $oldValues = $goal->toArray();
        $goal->delete();

        AuditLog::record('goal.deleted', $goal, $oldValues, null, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Financial goal deleted successfully.',
        ]);
    }
}

