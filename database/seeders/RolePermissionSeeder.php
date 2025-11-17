<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view dashboard',
            'manage spare parts',
            'manage stock',
            'manage suppliers',
            'manage categories',
            'manage locations',
            'manage purchase orders',
            'manage service jobs',
            'manage low stock alerts',
            'manage markup rules',
            'view audit logs',
            'export data',
            'import data',
            'manage users',
            'manage roles',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo(Permission::all());

        $storeKeeper = Role::create(['name' => 'store-keeper']);
        $storeKeeper->givePermissionTo([
            'view dashboard',
            'manage spare parts',
            'manage stock',
            'manage suppliers',
            'manage categories',
            'manage locations',
            'manage purchase orders',
            'manage low stock alerts',
            'manage markup rules',
            'export data',
            'import data',
        ]);

        $mechanic = Role::create(['name' => 'mechanic']);
        $mechanic->givePermissionTo([
            'view dashboard',
            'manage service jobs',
            'view spare parts',
            'view stock',
        ]);
    }
}
