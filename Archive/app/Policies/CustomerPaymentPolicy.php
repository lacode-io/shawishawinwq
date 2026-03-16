<?php

namespace App\Policies;

use App\Models\CustomerPayment;
use App\Models\User;

class CustomerPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_customer_payments');
    }

    public function view(User $user, CustomerPayment $customerPayment): bool
    {
        return $user->hasPermissionTo('view_customer_payments');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_customer_payments');
    }

    public function update(User $user, CustomerPayment $customerPayment): bool
    {
        return $user->hasPermissionTo('update_customer_payments');
    }

    public function delete(User $user, CustomerPayment $customerPayment): bool
    {
        return $user->hasPermissionTo('delete_customer_payments');
    }

    public function restore(User $user, CustomerPayment $customerPayment): bool
    {
        return $user->hasPermissionTo('delete_customer_payments');
    }

    public function forceDelete(User $user, CustomerPayment $customerPayment): bool
    {
        return $user->hasPermissionTo('delete_customer_payments');
    }
}
