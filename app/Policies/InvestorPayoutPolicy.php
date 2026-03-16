<?php

namespace App\Policies;

use App\Models\InvestorPayout;
use App\Models\User;

class InvestorPayoutPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_investor_payouts');
    }

    public function view(User $user, InvestorPayout $investorPayout): bool
    {
        return $user->hasPermissionTo('view_investor_payouts');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_investor_payouts');
    }

    public function update(User $user, InvestorPayout $investorPayout): bool
    {
        return $user->hasPermissionTo('update_investor_payouts');
    }

    public function delete(User $user, InvestorPayout $investorPayout): bool
    {
        return $user->hasPermissionTo('delete_investor_payouts');
    }

    public function restore(User $user, InvestorPayout $investorPayout): bool
    {
        return $user->hasPermissionTo('delete_investor_payouts');
    }

    public function forceDelete(User $user, InvestorPayout $investorPayout): bool
    {
        return $user->hasPermissionTo('delete_investor_payouts');
    }
}
