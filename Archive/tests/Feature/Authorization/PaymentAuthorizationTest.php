<?php

use App\Models\Customer;
use App\Models\CustomerPayment;

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
});

it('allows accountant to create payments via policy', function () {
    $user = createAccountant();

    expect($user->can('create', CustomerPayment::class))->toBeTrue();
});

it('denies viewer from creating payments via policy', function () {
    $user = createViewer();

    expect($user->can('create', CustomerPayment::class))->toBeFalse();
});

it('denies accountant from deleting payments via policy', function () {
    $user = createAccountant();
    $payment = CustomerPayment::factory()->create();

    expect($user->can('delete', $payment))->toBeFalse();
});

it('allows super_admin to delete payments via policy', function () {
    $user = createSuperAdmin();
    $payment = CustomerPayment::factory()->create();

    expect($user->can('delete', $payment))->toBeTrue();
});

it('denies viewer from updating payments via policy', function () {
    $user = createViewer();
    $payment = CustomerPayment::factory()->create();

    expect($user->can('update', $payment))->toBeFalse();
});

it('allows accountant to update payments via policy', function () {
    $user = createAccountant();
    $payment = CustomerPayment::factory()->create();

    expect($user->can('update', $payment))->toBeTrue();
});
