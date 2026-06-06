<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UnitPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->organization_id === $unit->building->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Owner', 'Manager']);
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->organization_id === $unit->building->organization_id 
            && $user->hasAnyRole(['Owner', 'Manager']);
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->organization_id === $unit->building->organization_id 
            && $user->hasRole('Owner');
    }

    public function restore(User $user, Unit $unit): bool
    {
        return $user->hasRole('Owner');
    }

    public function forceDelete(User $user, Unit $unit): bool
    {
        return $user->hasRole('Owner');
    }
}
