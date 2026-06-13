<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->value === 'owner';
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        return $user->role->value === 'owner'
            && $activityLog->organization_id === $user->organization_id;
    }
}
