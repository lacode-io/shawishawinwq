<?php

use App\Filament\Resources\InvestorResource\Pages\CreateInvestor;
use App\Filament\Resources\InvestorResource\Pages\EditInvestor;
use App\Filament\Resources\InvestorResource\Pages\ListInvestors;
use App\Models\Investor;
use App\Models\InvestorPayout;

use function Pest\Livewire\livewire;

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
});

it('allows super_admin to list investors', function () {
    $this->actingAs(createSuperAdmin());

    livewire(ListInvestors::class)->assertSuccessful();
});

it('allows accountant to list investors', function () {
    $this->actingAs(createAccountant());

    livewire(ListInvestors::class)->assertSuccessful();
});

it('allows viewer to list investors', function () {
    $this->actingAs(createViewer());

    livewire(ListInvestors::class)->assertSuccessful();
});

it('allows accountant to access create investor page', function () {
    $this->actingAs(createAccountant());

    livewire(CreateInvestor::class)->assertSuccessful();
});

it('denies viewer from accessing create investor page', function () {
    $this->actingAs(createViewer());

    livewire(CreateInvestor::class)->assertForbidden();
});

it('allows accountant to access edit investor page', function () {
    $this->actingAs(createAccountant());
    $investor = Investor::factory()->create();

    livewire(EditInvestor::class, ['record' => $investor->getRouteKey()])
        ->assertSuccessful();
});

it('denies viewer from accessing edit investor page', function () {
    $this->actingAs(createViewer());
    $investor = Investor::factory()->create();

    livewire(EditInvestor::class, ['record' => $investor->getRouteKey()])
        ->assertForbidden();
});

it('denies accountant from deleting investors via policy', function () {
    $user = createAccountant();

    expect($user->hasPermissionTo('delete_investors'))->toBeFalse();
});

it('allows accountant to create payouts via policy', function () {
    $user = createAccountant();

    expect($user->can('create', InvestorPayout::class))->toBeTrue();
});

it('denies viewer from creating payouts via policy', function () {
    $user = createViewer();

    expect($user->can('create', InvestorPayout::class))->toBeFalse();
});

it('denies accountant from deleting payouts via policy', function () {
    $user = createAccountant();
    $payout = InvestorPayout::factory()->create();

    expect($user->can('delete', $payout))->toBeFalse();
});
