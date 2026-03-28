<?php

use App\Enums\UserStatus;
use App\Models\Floor;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

test('manager rooms page returns room prices in dollars', function () {
    $manager = createApprovedManager();

    $floor = Floor::query()->create([
        'name' => 'First Floor',
        'number' => '1000',
        'created_by' => $manager->id,
    ]);

    $room = Room::query()->create([
        'floor_id' => $floor->id,
        'number' => '1001',
        'capacity' => 3,
        'price' => 12500,
        'created_by' => $manager->id,
    ]);

    $this->actingAs($manager)
        ->get(route('manager.rooms.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Manager/Rooms/Index')
            ->where('rooms.data.0.id', $room->id)
            ->where('rooms.data.0.number', '1001')
            ->where('rooms.data.0.price_in_dollars', '125.00')
            ->where('floors.0.id', $floor->id)
            ->where('floors.0.name', 'First Floor')
            ->where('floors.0.number', $floor->fresh()->number)
            ->where('rooms.data.0.floor.name', 'First Floor'),
        );
});

test('manager room prices are stored in cents when creating rooms', function () {
    $manager = createApprovedManager();

    $floor = Floor::query()->create([
        'name' => 'Second Floor',
        'number' => '2000',
        'created_by' => $manager->id,
    ]);

    $this->actingAs($manager)
        ->post(route('manager.rooms.store'), [
            'floor_id' => $floor->id,
            'number' => '2001',
            'capacity' => 2,
            'price' => '99.99',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('rooms', [
        'floor_id' => $floor->id,
        'number' => '2001',
        'capacity' => 2,
        'price' => 9999,
        'created_by' => $manager->id,
    ]);
});

test('manager can not delete a reserved room', function () {
    $manager = createApprovedManager();
    $clientRole = Role::findOrCreate('Client');

    $client = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $client->assignRole($clientRole);

    $floor = Floor::query()->create([
        'name' => 'Third Floor',
        'number' => '3000',
        'created_by' => $manager->id,
    ]);

    $room = Room::query()->create([
        'floor_id' => $floor->id,
        'number' => '3001',
        'capacity' => 4,
        'price' => 15000,
        'created_by' => $manager->id,
    ]);

    Reservation::query()->create([
        'user_id' => $client->id,
        'room_id' => $room->id,
        'accompany_number' => 2,
        'paid_price' => 15000,
        'check_in' => now()->addDay()->toDateString(),
        'check_out' => now()->addDays(3)->toDateString(),
    ]);

    $this->actingAs($manager)
        ->from(route('manager.rooms.index'))
        ->delete(route('manager.rooms.destroy', $room))
        ->assertRedirect(route('manager.rooms.index'))
        ->assertSessionHas('error', 'Cannot delete a room that has reservations.');

    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
    ]);
});

test('manager can delete an unreserved room', function () {
    $manager = createApprovedManager();

    $floor = Floor::query()->create([
        'name' => 'Fourth Floor',
        'number' => '4000',
        'created_by' => $manager->id,
    ]);

    $room = Room::query()->create([
        'floor_id' => $floor->id,
        'number' => '4001',
        'capacity' => 2,
        'price' => 17500,
        'created_by' => $manager->id,
    ]);

    $this->actingAs($manager)
        ->from(route('manager.rooms.index'))
        ->delete(route('manager.rooms.destroy', $room))
        ->assertRedirect(route('manager.rooms.index'))
        ->assertSessionHas('success', 'Room deleted successfully.');

    $this->assertDatabaseMissing('rooms', [
        'id' => $room->id,
    ]);
});

function createApprovedManager(): User
{
    $managerRole = Role::findOrCreate('Manager');

    $manager = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $manager->assignRole($managerRole);

    return $manager;
}
