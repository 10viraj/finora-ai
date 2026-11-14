<?php

namespace App\Policies;

use App\Models\FinancialGoal;
use App\Models\User;

class FinancialGoalPolicy
{
    /**
     * Determine whether the user can view the financial goal.
     */
    public function view(User $user, FinancialGoal $goal): bool
    {
        return (int) $user->id === (int) $goal->user_id;
    }

    /**
     * Determine whether the user can update the financial goal.
     */
    public function update(User $user, FinancialGoal $goal): bool
    {
        return (int) $user->id === (int) $goal->user_id;
    }

    /**
     * Determine whether the user can delete the financial goal.
     */
    public function delete(User $user, FinancialGoal $goal): bool
    {
        return (int) $user->id === (int) $goal->user_id;
    }
}

