<?php

use App\Enums\UserStatus;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('receptionist can approve a pending client', function () {
    Role::findOrCreate('Receptionist');
    Role::findOrCreate('Client');

    $receptionist = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $receptionist->assignRole('Receptionist');

    $client = User::factory()->create([
        'status' => UserStatus::Pending,
        'approved_by' => null,
        'approved_at' => null,
    ]);
    $client->assignRole('Client');

    $response = $this
        ->actingAs($receptionist)
        ->from(route('dashboard'))
        ->patch(route('clients.approve', $client));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('success', 'Client approved successfully.');

    $client->refresh();

    expect($client->status)->toBe(UserStatus::Approved)
        ->and($client->approved_by)->toBe($receptionist->id)
        ->and($client->approved_at)->not->toBeNull();
});

test('user without approval role can not approve pending clients', function () {
    Role::findOrCreate('Client');

    $actor = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $actor->assignRole('Client');

    $client = User::factory()->create([
        'status' => UserStatus::Pending,
    ]);
    $client->assignRole('Client');

    $response = $this
        ->actingAs($actor)
        ->patch(route('clients.approve', $client));

    $response->assertForbidden();

    $client->refresh();

    expect($client->status)->toBe(UserStatus::Pending)
        ->and($client->approved_by)->toBeNull()
        ->and($client->approved_at)->toBeNull();
});

test('already approved client can not be approved again', function () {
    Role::findOrCreate('Receptionist');
    Role::findOrCreate('Client');
    Role::findOrCreate('Admin');

    $admin = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $admin->assignRole('Admin');

    $receptionist = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $receptionist->assignRole('Receptionist');

    $client = User::factory()->create([
        'status' => UserStatus::Approved,
        'approved_by' => $admin->id,
        'approved_at' => now()->subDay(),
    ]);
    $client->assignRole('Client');

    $response = $this
        ->actingAs($receptionist)
        ->patch(route('clients.approve', $client));

    $response->assertForbidden();

    $client->refresh();

    expect($client->status)->toBe(UserStatus::Approved)
        ->and($client->approved_by)->toBe($admin->id);
});

test('non client users can not be approved as clients', function () {
    Role::findOrCreate('Admin');
    Role::findOrCreate('Manager');

    $admin = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $admin->assignRole('Admin');

    $manager = User::factory()->create([
        'status' => UserStatus::Pending,
        'approved_by' => null,
        'approved_at' => null,
    ]);
    $manager->assignRole('Manager');

    $response = $this
        ->actingAs($admin)
        ->patch(route('clients.approve', $manager));

    $response->assertForbidden();

    $manager->refresh();

    expect($manager->status)->toBe(UserStatus::Pending)
        ->and($manager->approved_by)->toBeNull()
        ->and($manager->approved_at)->toBeNull();
});
