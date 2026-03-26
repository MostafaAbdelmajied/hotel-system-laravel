<?php

use App\Enums\UserStatus;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

dataset('restricted staff roles', [
    'Manager',
    'Receptionist',
]);

test('manager and receptionist can access my approved clients page with only their approved clients', function (string $role) {
    Role::findOrCreate($role);
    Role::findOrCreate('Client');
    Role::findOrCreate('Manager');

    $staff = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $staff->assignRole($role);

    $otherApprover = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $otherApprover->assignRole('Manager');

    $clientApprovedByStaff = User::factory()->create([
        'status' => UserStatus::Approved,
        'approved_by' => $staff->id,
        'approved_at' => now()->subHour(),
    ]);
    $clientApprovedByStaff->assignRole('Client');

    $clientApprovedByOtherUser = User::factory()->create([
        'status' => UserStatus::Approved,
        'approved_by' => $otherApprover->id,
        'approved_at' => now()->subMinutes(30),
    ]);
    $clientApprovedByOtherUser->assignRole('Client');

    $pendingClientApprovedByStaff = User::factory()->create([
        'status' => UserStatus::Pending,
        'approved_by' => $staff->id,
        'approved_at' => now(),
    ]);
    $pendingClientApprovedByStaff->assignRole('Client');

    $approvedManagerByStaff = User::factory()->create([
        'status' => UserStatus::Approved,
        'approved_by' => $staff->id,
        'approved_at' => now(),
    ]);
    $approvedManagerByStaff->assignRole('Manager');

    $this->actingAs($staff)
        ->get(route('clients.my-approved'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('clients/my-approved-clients')
            ->where('approvedClients.data', function ($clients) use ($clientApprovedByStaff, $clientApprovedByOtherUser, $pendingClientApprovedByStaff, $approvedManagerByStaff): bool {
                return count($clients) === 1
                    && $clients[0]['id'] === $clientApprovedByStaff->id
                    && $clients[0]['email'] === $clientApprovedByStaff->email
                    && $clients[0]['country'] === $clientApprovedByStaff->country
                    && $clients[0]['gender'] === $clientApprovedByStaff->gender->value
                    && $clients[0]['approved_at'] !== null
                    && $clients[0]['id'] !== $clientApprovedByOtherUser->id
                    && $clients[0]['id'] !== $pendingClientApprovedByStaff->id
                    && $clients[0]['id'] !== $approvedManagerByStaff->id;
            }),
        );
})->with('restricted staff roles');

test('admin can access my approved clients page with all approved clients', function () {
    Role::findOrCreate('Admin');
    Role::findOrCreate('Client');
    Role::findOrCreate('Manager');

    $admin = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $admin->assignRole('Admin');

    $otherApprover = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $otherApprover->assignRole('Manager');

    $clientApprovedByAdmin = User::factory()->create([
        'status' => UserStatus::Approved,
        'approved_by' => $admin->id,
        'approved_at' => now()->subHour(),
    ]);
    $clientApprovedByAdmin->assignRole('Client');

    $clientApprovedByOtherUser = User::factory()->create([
        'status' => UserStatus::Approved,
        'approved_by' => $otherApprover->id,
        'approved_at' => now()->subMinutes(30),
    ]);
    $clientApprovedByOtherUser->assignRole('Client');

    $pendingClient = User::factory()->create([
        'status' => UserStatus::Pending,
        'approved_by' => $admin->id,
        'approved_at' => now(),
    ]);
    $pendingClient->assignRole('Client');

    $approvedManager = User::factory()->create([
        'status' => UserStatus::Approved,
        'approved_by' => $admin->id,
        'approved_at' => now(),
    ]);
    $approvedManager->assignRole('Manager');

    $this->actingAs($admin)
        ->get(route('clients.my-approved'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('clients/my-approved-clients')
            ->where('approvedClients.data', function ($clients) use ($clientApprovedByAdmin, $clientApprovedByOtherUser, $pendingClient, $approvedManager): bool {
                $clientIds = collect($clients)->pluck('id');

                return count($clients) === 2
                    && $clientIds->contains($clientApprovedByAdmin->id)
                    && $clientIds->contains($clientApprovedByOtherUser->id)
                    && ! $clientIds->contains($pendingClient->id)
                    && ! $clientIds->contains($approvedManager->id);
            }),
        );
});

test('non staff user can not access my approved clients page', function () {
    Role::findOrCreate('Client');

    $client = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $client->assignRole('Client');

    $this->actingAs($client)
        ->get(route('clients.my-approved'))
        ->assertForbidden();
});

test('my approved clients page is paginated', function () {
    Role::findOrCreate('Admin');
    Role::findOrCreate('Client');

    $admin = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $admin->assignRole('Admin');

    User::factory()->count(11)->create([
        'status' => UserStatus::Approved,
        'approved_by' => $admin->id,
        'approved_at' => now(),
    ])->each(function (User $user): void {
        $user->assignRole('Client');
    });

    $this->actingAs($admin)
        ->get(route('clients.my-approved'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('clients/my-approved-clients')
            ->where('approvedClients.per_page', 10)
            ->where('approvedClients.total', 11)
            ->where('approvedClients.current_page', 1)
            ->where('approvedClients.last_page', 2),
        );
});
