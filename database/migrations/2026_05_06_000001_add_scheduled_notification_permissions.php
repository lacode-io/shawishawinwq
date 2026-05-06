<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view_scheduled_notifications',
            'manage_scheduled_notifications',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // المدير يأخذ الكل
        if ($admin = Role::where('name', 'super_admin')->where('guard_name', 'web')->first()) {
            $admin->givePermissionTo($permissions);
        }

        // المحاسب — صلاحيات كاملة
        if ($accountant = Role::where('name', 'accountant')->where('guard_name', 'web')->first()) {
            $accountant->givePermissionTo($permissions);
        }

        // المشاهد — قراءة فقط
        if ($viewer = Role::where('name', 'viewer')->where('guard_name', 'web')->first()) {
            $viewer->givePermissionTo('view_scheduled_notifications');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', [
            'view_scheduled_notifications',
            'manage_scheduled_notifications',
        ])->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
