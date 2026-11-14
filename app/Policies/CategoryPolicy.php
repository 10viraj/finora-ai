<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determine whether the user can view the category.
     */
    public function view(User $user, Category $category): bool
    {
        return $category->is_system || $category->is_default || (int) $user->id === (int) $category->user_id;
    }

    /**
     * Determine whether the user can update the category.
     */
    public function update(User $user, Category $category): bool
    {
        return !$category->is_system && !$category->is_default && (int) $user->id === (int) $category->user_id;
    }

    /**
     * Determine whether the user can delete the category.
     */
    public function delete(User $user, Category $category): bool
    {
        return !$category->is_system && !$category->is_default && (int) $user->id === (int) $category->user_id;
    }
}

