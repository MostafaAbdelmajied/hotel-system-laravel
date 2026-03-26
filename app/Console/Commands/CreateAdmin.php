<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('create:admin {--name=} {--email=}')]
#[Description('Command for manually creating an admin user')]
class CreateAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->option('name') ?? $this->ask('Enter the name of the admin user');
        $email = $this->option('email') ?? $this->ask('Enter the email of the admin user');
        $password = $this->ask('Enter the password for the admin user');

        $validation = validator(compact('name', 'email', 'password'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);
        if ($validation->fails()) {
            foreach ($validation->errors()->all() as $error) {
                $this->error($error);
            }
            return;
        }

        $user = \App\Models\User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => true,
        ]);

        if ($user) {
            $user->assignRole('Admin');
            $this->info('Admin user created successfully!');
        } else {
            $this->error('Failed to create admin user.');
        }
    }
}
