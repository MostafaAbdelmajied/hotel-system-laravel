<?php

namespace App\Policies;

use App\Models\User;

class ReservationPolicy
{
    public function viewOwnReservations(User $user): bool
    {
        return $user->hasRole('Client');
    }

    public function viewAssignedClientReservations(User $user): bool
    {
        return $user->hasRole('Receptionist');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Client');
    }

    public function confirmPayment(User $user): bool
    {
        return $user->hasRole('Client');
    }
}
