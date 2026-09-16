<?php

namespace App\Policies;

use App\Models\User;

/**
 * Orders come from customers; the panel can view them and change status, never create or delete.
 */
class OrderPolicy
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
        return false;
    }

    public function update(User $user): bool
    {
        return $user->canManage();
    }

    public function delete(User $user): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
