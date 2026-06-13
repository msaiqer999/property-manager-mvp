<?php

namespace App\Support;

use App\Models\User;

class ReportAuthorization
{
    public function viewReports(User $user): bool
    {
        return $user->role->can('view-reports')
            && $user->role->value !== 'caretaker';
    }

    public function exportPdf(User $user, string $type): bool
    {
        return $this->viewReports($user);
    }

    public function viewProfitData(User $user): bool
    {
        return $this->viewReports($user);
    }
}
