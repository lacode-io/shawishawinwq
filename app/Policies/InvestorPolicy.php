<?php

namespace App\Policies;

use App\Models\Investor;
use App\Models\User;

class InvestorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_investors');
    }

    public function view(User $user, Investor $investor): bool
    {
        return $user->hasPermissionTo('view_investors');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_investors');
    }

    public function update(User $user, Investor $investor): bool
    {
        return $user->hasPermissionTo('update_investors');
    }

    public function delete(User $user, Investor $investor): bool
    {
        return $user->hasPermissionTo('delete_investors');
    }

    public function restore(User $user, Investor $investor): bool
    {
        return $user->hasPermissionTo('delete_investors');
    }

    public function forceDelete(User $user, Investor $investor): bool
    {
        return $user->hasPermissionTo('delete_investors');
    }
}
