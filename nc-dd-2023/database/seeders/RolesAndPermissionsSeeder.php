<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        Permission::create(['name' => 'client']); // Role for clients

        // Roles for admin panel access
        Permission::create(['name' => 'roles-edit']);
        Permission::create(['name' => 'roles-create']);
        Permission::create(['name' => 'roles-view']);
        Permission::create(['name' => 'roles-delete']);

        Permission::create(['name' => 'users-edit']);
        Permission::create(['name' => 'users-create']);
        Permission::create(['name' => 'users-view']);
        Permission::create(['name' => 'users-delete']);

        // create roles and assign created permissions

        // this can be done as separate statements
        $role = Role::create(['name' => 'client']);
        $role->givePermissionTo('client');

        $role = Role::create(['name' => 'administrator']);
        $role->givePermissionTo(Permission::all());
    }
}
