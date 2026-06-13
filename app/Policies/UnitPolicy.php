<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->can('manage-properties');
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->role->can('manage-properties')
            && $this->belongsToUsersOrganization($user, $unit);
    }

    public function create(User $user): bool
    {
        return $user->role->can('manage-properties');
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->role->can('manage-properties')
            && $this->belongsToUsersOrganization($user, $unit);
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->role->value === 'owner'
            && $this->belongsToUsersOrganization($user, $unit);
    }

    private function belongsToUsersOrganization(User $user, Unit $unit): bool
    {
        return $unit->building()
            ->where('organization_id', $user->organization_id)
            ->exists();
    }
}
