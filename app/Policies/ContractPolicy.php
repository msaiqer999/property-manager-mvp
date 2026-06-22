<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->can('manage-contracts');
    }

    public function view(User $user, Contract $contract): bool
    {
        return $user->role->can('manage-contracts')
            && $contract->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->role->can('manage-contracts');
    }

    public function update(User $user, Contract $contract): bool
    {
        return $user->role->can('manage-contracts')
            && ! in_array($user->role->value, ['accountant', 'caretaker'], true)
            && $contract->organization_id === $user->organization_id;
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $user->role->value === 'owner'
            && $contract->organization_id === $user->organization_id;
    }

    public function terminate(User $user, Contract $contract): bool
    {
        return $user->role === Role::Owner
            && $contract->organization_id === $user->organization_id;
    }

    public function exportPdf(User $user, Contract $contract): bool
    {
        return $this->view($user, $contract);
    }
}
