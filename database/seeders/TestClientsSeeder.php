<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class TestClientsSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('Client');

        for ($i = 3; $i <= 13; $i++) {
            $user = User::updateOrCreate(
                ['email' => "test{$i}@gamil.com"],
                [
                    'name' => "test{$i}",
                    'password' => '123456',
                    'country' => 'Egypt',
                    'gender' => $i % 2 === 0 ? 'female' : 'male',
                    'status' => UserStatus::Pending,
                    'approved_by' => null,
                    'approved_at' => null,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles(['Client']);
        }
    }
}
