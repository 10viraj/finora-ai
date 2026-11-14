<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;

class BudgetPolicy
{
    /**
     * Determine whether the user can view the budget.
     */
    public function view(User $user, Budget $budget): bool
    {
        return (int) $user->id === (int) $budget->user_id;
    }

    /**
     * Determine whether the user can update the budget.
     */
    public function update(User $user, Budget $budget): bool
    {
        return (int) $user->id === (int) $budget->user_id;
    }

    /**
     * Determine whether the user can delete the budget.
     */
    public function delete(User $user, Budget $budget): bool
    {
        return (int) $user->id === (int) $budget->user_id;
    }
}

