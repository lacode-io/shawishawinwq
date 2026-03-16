<?php

use App\Filament\Pages\FinanceDashboard;
use App\Filament\Pages\ManageSettings;
use App\Models\Setting;

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    Setting::firstOrCreate(['id' => 1], [
        'site_name' => 'شوي شوي',
        'primary_color' => '#0ea5e9',
        'secondary_color' => '#8b5cf6',
    ]);
});

it('allows super_admin to access finance dashboard', function () {
    $this->actingAs(createSuperAdmin());

    expect(FinanceDashboard::canAccess())->toBeTrue();
});

it('allows accountant to access finance dashboard', function () {
    $this->actingAs(createAccountant());

    expect(FinanceDashboard::canAccess())->toBeTrue();
});

it('allows viewer to access finance dashboard', function () {
    $this->actingAs(createViewer());

    expect(FinanceDashboard::canAccess())->toBeTrue();
});

it('denies user without permission from accessing finance dashboard', function () {
    seedPermissions();
    $user = \App\Models\User::factory()->create();
    $this->actingAs($user);

    expect(FinanceDashboard::canAccess())->toBeFalse();
});

it('allows super_admin to access settings', function () {
    $this->actingAs(createSuperAdmin());

    expect(ManageSettings::canAccess())->toBeTrue();
});

it('denies accountant from accessing settings', function () {
    $this->actingAs(createAccountant());

    expect(ManageSettings::canAccess())->toBeFalse();
});

it('denies viewer from accessing settings', function () {
    $this->actingAs(createViewer());

    expect(ManageSettings::canAccess())->toBeFalse();
});
