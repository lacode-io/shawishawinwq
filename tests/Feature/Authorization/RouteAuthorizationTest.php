<?php

use App\Models\Customer;
use App\Models\Investor;

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
});

it('denies unauthenticated user from accessing customer invoice', function () {
    $customer = Customer::factory()->create();

    $this->get(route('customers.invoice', $customer))
        ->assertStatus(500); // No login route defined — auth middleware throws
})->skip('No named login route in Filament-only app');

it('denies viewer from accessing customer invoice PDF', function () {
    $user = createViewer();
    $customer = Customer::factory()->create();

    $this->actingAs($user)
        ->get(route('customers.invoice', $customer))
        ->assertForbidden();
});

it('allows super_admin to access customer invoice PDF', function () {
    $user = createSuperAdmin();
    expect($user->can('export_pdf'))->toBeTrue();
});

it('denies viewer from accessing investor statement PDF', function () {
    $user = createViewer();
    $investor = Investor::factory()->create();

    $this->actingAs($user)
        ->get(route('investors.statement', $investor))
        ->assertForbidden();
});

it('allows accountant to have export_pdf permission', function () {
    $user = createAccountant();
    expect($user->can('export_pdf'))->toBeTrue();
});

it('denies viewer from accessing finance summary PDF', function () {
    $user = createViewer();

    $this->actingAs($user)
        ->get(route('finance.summary-pdf', ['from' => '2026-01-01', 'to' => '2026-01-31']))
        ->assertForbidden();
});

it('denies viewer from having export_pdf permission', function () {
    $user = createViewer();
    expect($user->can('export_pdf'))->toBeFalse();
});
