<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class StaffAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::findOrCreate('Manager');
        Role::findOrCreate('Receptionist');

        $staffAccounts = [
            ['name' => 'manager1', 'email' => 'manager1@gmail.com', 'role' => 'Manager', 'country' => 'Egypt', 'gender' => 'male'],
            ['name' => 'manager2', 'email' => 'manager2@gmail.com', 'role' => 'Manager', 'country' => 'Egypt', 'gender' => 'female'],
            ['name' => 'manager3', 'email' => 'manager3@gmail.com', 'role' => 'Manager', 'country' => 'Egypt', 'gender' => 'male'],
            ['name' => 'receptionist1', 'email' => 'receptionist1@gmail.com', 'role' => 'Receptionist', 'country' => 'Egypt', 'gender' => 'female'],
            ['name' => 'receptionist2', 'email' => 'receptionist2@gmail.com', 'role' => 'Receptionist', 'country' => 'Egypt', 'gender' => 'male'],
            ['name' => 'receptionist3', 'email' => 'receptionist3@gmail.com', 'role' => 'Receptionist', 'country' => 'Egypt', 'gender' => 'female'],
        ];

        foreach ($staffAccounts as $staffAccount) {
            $user = User::updateOrCreate(
                ['email' => $staffAccount['email']],
                [
                    'name' => $staffAccount['name'],
                    'password' => '123456',
                    'country' => $staffAccount['country'],
                    'gender' => $staffAccount['gender'],
                    'status' => UserStatus::Approved,
                    'approved_by' => null,
                    'approved_at' => null,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$staffAccount['role']]);
        }
    }
}
