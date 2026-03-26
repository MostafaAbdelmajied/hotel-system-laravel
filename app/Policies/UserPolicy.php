<?php

namespace App\Policies;

use App\Enums\UserStatus;
use App\Models\User;

class UserPolicy
{
    public function viewPendingClients(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Manager', 'Receptionist']);
    }

    public function viewMyApprovedClients(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Manager', 'Receptionist']);
    }

    public function approveClient(User $user, User $client): bool
    {
        if (! $user->hasAnyRole(['Admin', 'Manager', 'Receptionist'])) {
            return false;
        }

        if (! $client->hasRole('Client')) {
            return false;
        }

        return $client->status === UserStatus::Pending;
    }
}
