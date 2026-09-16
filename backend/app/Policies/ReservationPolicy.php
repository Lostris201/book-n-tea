<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy extends ManagerPolicy
{
    /** External reservations belong to the provider; cancel them via status instead of deleting. */
    public function delete(User $user, ?Reservation $reservation = null): bool
    {
        return $user->canManage() && (! $reservation || ! $reservation->isExternal());
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
