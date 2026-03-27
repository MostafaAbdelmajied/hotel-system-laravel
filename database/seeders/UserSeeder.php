<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = Role::whereNot('name', 'Admin')->get();
        foreach ($roles as $i => $role) {
            User::factory()
                ->count(5)
                ->create([
                    'status' => $role->name === 'Client' ? \App\Enums\UserStatus::Pending : \App\Enums\UserStatus::Approved,
                ])
                ->each(function ($user, $index) use($role) {
                    $user->name = $role->name . ' User' . ($index + 1);
                    $user->email = strtolower($role->name) . ($index + 1) . '@example.com';
                    $user->save();
                    $user->assignRole($role->name);
                });
        }
    }
}
