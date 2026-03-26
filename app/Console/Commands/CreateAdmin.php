<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('create:admin')]
#[Description('Command for manually creating an admin user')]
class CreateAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->ask('Enter the name of the admin user');
        $email = $this->ask('Enter the email of the admin user');
        $password = $this->secret('Enter the password for the admin user');

        $user = \App\Models\User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => true,
        ]);

        if ($user) {
            $this->info('Admin user created successfully!');
        } else {
            $this->error('Failed to create admin user.');
        }
    }
}
