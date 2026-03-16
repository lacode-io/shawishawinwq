<?php

use App\Filament\Resources\CustomerResource\Pages\CreateCustomer;
use App\Filament\Resources\CustomerResource\Pages\EditCustomer;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Models\Customer;

use function Pest\Livewire\livewire;

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
});

it('allows super_admin to list customers', function () {
    $this->actingAs(createSuperAdmin());

    livewire(ListCustomers::class)->assertSuccessful();
});

it('allows accountant to list customers', function () {
    $this->actingAs(createAccountant());

    livewire(ListCustomers::class)->assertSuccessful();
});

it('allows viewer to list customers', function () {
    $this->actingAs(createViewer());

    livewire(ListCustomers::class)->assertSuccessful();
});

it('allows accountant to access create customer page', function () {
    $this->actingAs(createAccountant());

    livewire(CreateCustomer::class)->assertSuccessful();
});

it('denies viewer from accessing create customer page', function () {
    $this->actingAs(createViewer());

    livewire(CreateCustomer::class)->assertForbidden();
});

it('allows accountant to access edit customer page', function () {
    $this->actingAs(createAccountant());
    $customer = Customer::factory()->create();

    livewire(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->assertSuccessful();
});

it('denies viewer from accessing edit customer page', function () {
    $this->actingAs(createViewer());
    $customer = Customer::factory()->create();

    livewire(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->assertForbidden();
});

it('denies accountant from deleting customers via policy', function () {
    $user = createAccountant();

    expect($user->hasPermissionTo('delete_customers'))->toBeFalse();
});

it('allows super_admin to delete customers via policy', function () {
    $user = createSuperAdmin();
    $customer = Customer::factory()->create();

    expect($user->can('delete', $customer))->toBeTrue();
});
