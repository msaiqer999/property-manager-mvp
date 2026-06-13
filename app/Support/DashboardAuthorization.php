<?php

namespace App\Support;

use App\Models\User;

class DashboardAuthorization
{
    public function viewDashboard(User $user): bool
    {
        return $user->organization_id !== null;
    }
}
