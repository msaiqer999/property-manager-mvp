<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->can('view-expenses');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->role->can('view-expenses')
            && $expense->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->role->can('manage-expenses');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->role->can('manage-expenses')
            && $expense->organization_id === $user->organization_id;
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->role === Role::Owner
            && $expense->organization_id === $user->organization_id;
    }

    public function uploadInvoice(User $user, Expense $expense): bool
    {
        return $this->update($user, $expense);
    }

    public function voidExpense(User $user, Expense $expense): bool
    {
        return $user->role === Role::Owner
            && $expense->organization_id === $user->organization_id;
    }
}
