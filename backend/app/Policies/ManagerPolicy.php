<?php

namespace App\Policies;

use App\Models\User;

/**
 * Admin + manager have full access: menu (categories, products, option groups, options) and tables.
 */
class ManagerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManage();
    }

    public function view(User $user): bool
    {
        return $user->canManage();
    }

    public function create(User $user): bool
    {
        return $user->canManage();
    }

    public function update(User $user): bool
    {
        return $user->canManage();
    }

    public function delete(User $user): bool
    {
        return $user->canManage();
    }

    public function deleteAny(User $user): bool
    {
        return $user->canManage();
    }

    public function reorder(User $user): bool
    {
        return $user->canManage();
    }
}
