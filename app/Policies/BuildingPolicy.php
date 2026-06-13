<?php

namespace App\Policies;

use App\Models\Building;
use App\Models\User;

class BuildingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->can('manage-properties');
    }

    public function view(User $user, Building $building): bool
    {
        return $user->role->can('manage-properties')
            && $building->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->role->can('manage-properties');
    }

    public function update(User $user, Building $building): bool
    {
        return $user->role->can('manage-properties')
            && $building->organization_id === $user->organization_id;
    }

    public function delete(User $user, Building $building): bool
    {
        return $user->role->value === 'owner'
            && $building->organization_id === $user->organization_id;
    }
}
