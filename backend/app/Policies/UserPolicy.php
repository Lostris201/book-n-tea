<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Admins cannot delete themselves (avoids locking everyone out). */
    public function delete(User $user, ?User $model = null): bool
    {
        return $user->isAdmin() && (! $model || ! $model->is($user));
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
