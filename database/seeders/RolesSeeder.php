<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Standard Permissions
        $permissions = [
            'view dashboard',
            'manage users',
            'manage roles',
            'view activity logs',
            'manage tenants',
            'manage billing',
            'view reports',
            'manage plans',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create Roles

        $ghost = Role::firstOrCreate(['name' => 'Ghost', 'guard_name' => 'web']);
        // Ghost gets all permissions via Gate::before, but explicitly assigning doesn't hurt for some checks
        //$ghost->givePermissionTo(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(['view dashboard', 'manage users', 'view activity logs', 'manage plans']);

        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $manager->givePermissionTo(['view dashboard', 'view reports']);

        $user = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);
        $user->givePermissionTo(['view dashboard']);
    }
}
