<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(
            [
                RolesAndPermissionsSeeder::class, StaffAccountsSeeder::class, FloorSeeder::class, RoomsSeeder::class,
            ]
        );

        // admin
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('123456'),
        ]);
        $admin->assignRole('Admin');

        $this->call(UserSeeder::class);
    }
}
