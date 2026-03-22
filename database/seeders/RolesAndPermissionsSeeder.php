<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ================================
        // Permissions
        // ================================
        $permissions = [
            // Receptionists
            'view receptionists',
            'create receptionist',
            'update receptionist',
            'delete receptionist',
            'ban receptionist',
            'unban receptionist',

            // Clients
            'view clients',
            'create client',
            'update client',
            'delete client',
            'approve client',

            // Floors
            'view floors',
            'create floor',
            'update floor',
            'delete floor',

            // Rooms
            'view rooms',
            'create room',
            'update room',
            'delete room',

            // Reservations
            'view reservations',
            'create reservation',

            // Special
            'view own data',
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // ================================
        // Roles
        // ================================

        // Admin
        $admin = Role::create([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);

        // Manager
        $manager = Role::create([
            'name' => 'Manager',
            'guard_name' => 'web',
        ]);

        // Receptionist
        $receptionist = Role::create([
            'name' => 'Receptionist',
            'guard_name' => 'web',
        ]);

        // Client
        $client = Role::create([
            'name' => 'Client',
            'guard_name' => 'web',
        ]);

        // ================================
        // Assign Permissions
        // ================================

        // Admin
        $admin->givePermissionTo(Permission::all());

        // Manager
        $manager->givePermissionTo([
            'view receptionists',
            'create receptionist',
            'update receptionist',
            'delete receptionist',
            'ban receptionist',
            'unban receptionist',

            'view clients',
            'create client',
            'update client',
            'delete client',

            'view floors',
            'create floor',
            'update floor',
            'delete floor',

            'view rooms',
            'create room',
            'update room',
            'delete room',
        ]);

        // Receptionist
        $receptionist->givePermissionTo([
            'view clients',
            'approve client',
            'view reservations',
        ]);

        // Client
        $client->givePermissionTo([
            'create reservation',
            'view own data',
        ]);
    }
}