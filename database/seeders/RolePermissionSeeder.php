<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.restore',
            'users.change-password',
            'users.assign-roles',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'roles.assign-permissions',
            'permissions.view',
            'permissions.create',
            'permissions.edit',
            'permissions.delete',
            'products.view',
            'products.create',
            'products.edit',
            'products.update',
            'products.delete',
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.update',
            'categories.delete',
            'inventory.view',
            'inventory.movements',
            'purchases.view',
            'purchases.create',
            'sales.view',
            'sales.create',
            'pos.access',
            'reports.view',
            'roles.manage',
            'users.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $allPermissions = Permission::pluck('name')->all();

        Role::findOrCreate('admin')->syncPermissions($allPermissions);
        Role::findOrCreate('super_admin')->syncPermissions($allPermissions);

        Role::findOrCreate('manager')->syncPermissions([
            'dashboard.view',
            'users.view',
            'products.view',
            'products.create',
            'products.edit',
            'products.update',
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.update',
            'inventory.view',
            'purchases.view',
            'sales.view',
            'reports.view',
        ]);

        Role::findOrCreate('cashier')->syncPermissions([
            'dashboard.view',
            'products.view',
            'categories.view',
            'sales.view',
            'sales.create',
            'pos.access',
        ]);

        Role::findOrCreate('warehouse')->syncPermissions([
            'dashboard.view',
            'products.view',
            'products.create',
            'products.edit',
            'products.update',
            'categories.view',
            'inventory.view',
            'inventory.movements',
            'purchases.view',
        ]);

        Role::findOrCreate('inventory_manager')->syncPermissions([
            'dashboard.view',
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.update',
            'products.view',
            'products.create',
            'products.edit',
            'products.update',
            'inventory.view',
            'inventory.movements',
            'reports.view',
        ]);

        Role::findOrCreate('viewer')->syncPermissions([
            'dashboard.view',
            'categories.view',
            'products.view',
            'reports.view',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
