<?php

namespace App\Policies;

use App\Models\CafeTable;
use App\Models\User;

class CafeTablePolicy extends ManagerPolicy
{
    /** Orders reference tables (restrict on delete); deactivate instead. */
    public function delete(User $user, ?CafeTable $table = null): bool
    {
        return $user->canManage() && (! $table || ! $table->orders()->exists());
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
