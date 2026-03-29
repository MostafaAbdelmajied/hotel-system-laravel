<?php

use App\Enums\UserStatus;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

it('shares reservations navigation permission for approved clients', function () {
    $clientRole = Role::findOrCreate('Client');

    $client = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $client->assignRole($clientRole);

    $this->actingAs($client)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.canViewReservations', true),
        );
});

it('does not share reservations navigation permission for managers', function () {
    $managerRole = Role::findOrCreate('Manager');

    $manager = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $manager->assignRole($managerRole);

    $this->actingAs($manager)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.canViewReservations', false),
        );
});
