<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->manageUsers($user);
    }

    public function view(User $user, User $target): bool
    {
        return $this->manageUsers($user)
            && $target->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $this->manageUsers($user);
    }

    public function update(User $user, User $target): bool
    {
        return $this->manageUsers($user)
            && $target->organization_id === $user->organization_id;
    }

    public function deactivate(User $user, User $target): bool
    {
        return $this->update($user, $target);
    }

    public function reactivate(User $user, User $target): bool
    {
        return $this->update($user, $target);
    }

    public function manageUsers(User $user): bool
    {
        return $user->is_active && $user->role->value === 'owner';
    }
}
