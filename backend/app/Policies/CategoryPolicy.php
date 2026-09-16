<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy extends ManagerPolicy
{
    /** Products reference categories (restrict on delete); deactivate instead. */
    public function delete(User $user, ?Category $category = null): bool
    {
        return $user->canManage() && (! $category || ! $category->products()->exists());
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
