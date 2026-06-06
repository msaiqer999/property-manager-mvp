<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActivityLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function view(User $user, ActivityLog $log): bool
    {
        return $user->organization_id === $log->user->organization_id;
    }

    public function viewAsCaretaker(User $user): bool
    {
        return !$user->hasRole('Caretaker');
    }
}
