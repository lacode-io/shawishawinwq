<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function seedPermissions(): void
{
    $permissions = [
        'view_customers', 'create_customers', 'update_customers', 'delete_customers',
        'view_customer_payments', 'create_customer_payments', 'update_customer_payments', 'delete_customer_payments',
        'view_investors', 'create_investors', 'update_investors', 'delete_investors',
        'view_investor_payouts', 'create_investor_payouts', 'update_investor_payouts', 'delete_investor_payouts',
        'view_expenses', 'create_expenses', 'update_expenses', 'delete_expenses',
        'view_finance_closings', 'create_finance_closings', 'update_finance_closings', 'delete_finance_closings',
        'view_users', 'create_users', 'update_users', 'delete_users',
        'view_roles', 'create_roles', 'update_roles', 'delete_roles',
        'view_app_notes', 'create_app_notes', 'update_app_notes', 'delete_app_notes',
        'view_activity_log', 'manage_settings', 'export_pdf',
        'view_finance_dashboard', 'mark_completed',
    ];

    foreach ($permissions as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }

    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
}

function createSuperAdmin(): User
{
    seedPermissions();
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $role->syncPermissions(Permission::all());
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function createAccountant(): User
{
    seedPermissions();
    $role = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
    $role->syncPermissions([
        'view_customers', 'create_customers', 'update_customers',
        'view_customer_payments', 'create_customer_payments', 'update_customer_payments',
        'view_investors', 'create_investors', 'update_investors',
        'view_investor_payouts', 'create_investor_payouts', 'update_investor_payouts',
        'view_expenses', 'create_expenses', 'update_expenses',
        'view_finance_closings', 'create_finance_closings', 'update_finance_closings',
        'view_app_notes', 'create_app_notes', 'update_app_notes',
        'view_finance_dashboard', 'mark_completed',
        'export_pdf',
    ]);
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function createViewer(): User
{
    seedPermissions();
    $role = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
    $role->syncPermissions([
        'view_customers', 'view_customer_payments',
        'view_investors', 'view_investor_payouts',
        'view_expenses', 'view_finance_closings',
        'view_app_notes',
        'view_activity_log', 'view_finance_dashboard',
    ]);
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}
