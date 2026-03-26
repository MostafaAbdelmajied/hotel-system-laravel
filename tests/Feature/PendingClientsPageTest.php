<?php

use App\Enums\UserStatus;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

dataset('approver roles', [
    'Admin',
    'Manager',
    'Receptionist',
]);

test('authorized staff can access pending clients page', function (string $role) {
    Role::findOrCreate($role);
    Role::findOrCreate('Client');
    Role::findOrCreate('Manager');

    $staff = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $staff->assignRole($role);

    $pendingClient = User::factory()->create([
        'status' => UserStatus::Pending,
    ]);
    $pendingClient->assignRole('Client');

    $approvedClient = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $approvedClient->assignRole('Client');

    $pendingManager = User::factory()->create([
        'status' => UserStatus::Pending,
    ]);
    $pendingManager->assignRole('Manager');

    $this->actingAs($staff)
        ->get(route('clients.pending'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('clients/pending-clients')
            ->where('pendingClients.data', function (array $clients) use ($pendingClient, $approvedClient, $pendingManager): bool {
                return count($clients) === 1
                    && $clients[0]['id'] === $pendingClient->id
                    && $clients[0]['email'] === $pendingClient->email
                    && $clients[0]['country'] === $pendingClient->country
                    && $clients[0]['gender'] === $pendingClient->gender->value
                    && $clients[0]['id'] !== $approvedClient->id
                    && $clients[0]['id'] !== $pendingManager->id;
            }),
        );
})->with('approver roles');

test('non staff user can not access pending clients page', function () {
    Role::findOrCreate('Client');
    Role::findOrCreate('Manager');

    $client = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $client->assignRole('Client');

    $this->actingAs($client)
        ->get(route('clients.pending'))
        ->assertForbidden();
});

test('pending clients page is paginated', function () {
    Role::findOrCreate('Admin');
    Role::findOrCreate('Client');
    Role::findOrCreate('Manager');

    $admin = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $admin->assignRole('Admin');

    User::factory()->count(11)->create([
        'status' => UserStatus::Pending,
    ])->each(function (User $user): void {
        $user->assignRole('Client');
    });

    $this->actingAs($admin)
        ->get(route('clients.pending'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('clients/pending-clients')
            ->where('pendingClients.per_page', 10)
            ->where('pendingClients.total', 11)
            ->where('pendingClients.current_page', 1)
            ->where('pendingClients.last_page', 2),
        );
});
