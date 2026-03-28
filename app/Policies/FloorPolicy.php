<?php

namespace App\Policies;

use App\Models\Floor;
use App\Models\User;

class FloorPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Manager']);

    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Floor $floor): bool
    {
        return $user->id === $floor->created_by || $user->id === $floor->managed_by;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Manager']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Floor $floor): bool
    {
        return $user->hasRole('Admin') || $user->id === $floor->managed_by;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Floor $floor): bool
    {
        return $user->hasRole('Admin') || $user->id === $floor->managed_by;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Floor $floor): bool
    {
        return $user->hasRole('Admin') || $user->id === $floor->managed_by;

    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Floor $floor): bool
    {
        return $user->hasRole('Admin') || $user->id === $floor->managed_by;
    }
}
