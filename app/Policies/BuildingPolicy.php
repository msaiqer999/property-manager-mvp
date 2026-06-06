<?php

namespace App\Policies;

use App\Models\Building;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BuildingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any buildings.
     */
    public function viewAny(User $user): bool
    {
        return $user->organization_id !== null;
    }

    /**
     * Determine if the user can view the building.
     */
    public function view(User $user, Building $building): bool
    {
        return $user->organization_id === $building->organization_id;
    }

    /**
     * Determine if the user can create buildings.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Owner', 'Manager']);
    }

    /**
     * Determine if the user can update the building.
     */
    public function update(User $user, Building $building): bool
    {
        return $user->organization_id === $building->organization_id 
            && $user->hasAnyRole(['Owner', 'Manager']);
    }

    /**
     * Determine if the user can delete the building.
     */
    public function delete(User $user, Building $building): bool
    {
        return $user->organization_id === $building->organization_id 
            && $user->hasRole('Owner');
    }

    /**
     * Determine if the user can restore the building.
     */
    public function restore(User $user, Building $building): bool
    {
        return $user->hasRole('Owner');
    }

    /**
     * Determine if the user can permanently delete the building.
     */
    public function forceDelete(User $user, Building $building): bool
    {
        return $user->hasRole('Owner');
    }
}
