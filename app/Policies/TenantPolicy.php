<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->can('manage-tenants');
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $user->role->can('manage-tenants')
            && $tenant->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->role->can('manage-tenants');
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->role->can('manage-tenants')
            && $tenant->organization_id === $user->organization_id;
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->role->value === 'owner'
            && $tenant->organization_id === $user->organization_id;
    }
}
