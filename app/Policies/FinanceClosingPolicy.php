<?php

namespace App\Policies;

use App\Models\FinanceClosing;
use App\Models\User;

class FinanceClosingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_finance_closings');
    }

    public function view(User $user, FinanceClosing $financeClosing): bool
    {
        return $user->hasPermissionTo('view_finance_closings');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_finance_closings');
    }

    public function update(User $user, FinanceClosing $financeClosing): bool
    {
        return $user->hasPermissionTo('update_finance_closings');
    }

    public function delete(User $user, FinanceClosing $financeClosing): bool
    {
        return $user->hasPermissionTo('delete_finance_closings');
    }

    public function restore(User $user, FinanceClosing $financeClosing): bool
    {
        return $user->hasPermissionTo('delete_finance_closings');
    }

    public function forceDelete(User $user, FinanceClosing $financeClosing): bool
    {
        return $user->hasPermissionTo('delete_finance_closings');
    }
}
