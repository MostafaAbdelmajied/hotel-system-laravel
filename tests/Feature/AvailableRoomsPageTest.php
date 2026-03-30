<?php

use App\Enums\UserStatus;
use App\Models\Floor;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('returns only available rooms for the requested date range', function () {
    $client = createApprovedClientForAvailableRoomsTest();

    $floor = Floor::query()->create([
        'name' => 'Booking Floor',
        'number' => '7000',
        'created_by' => $client->id,
    ]);

    $reservedRoom = Room::query()->create([
        'number' => '7001',
        'capacity' => 2,
        'price' => 12000,
        'floor_id' => $floor->id,
        'created_by' => $client->id,
    ]);

    $availableRoom = Room::query()->create([
        'number' => '7002',
        'capacity' => 3,
        'price' => 15000,
        'floor_id' => $floor->id,
        'created_by' => $client->id,
    ]);

    $requestCheckIn = now()->addDays(10)->toDateString();
    $requestCheckOut = now()->addDays(13)->toDateString();

    $reservation = [
        'room_id' => $reservedRoom->id,
        'accompany_number' => 1,
        'check_in' => now()->addDays(11)->toDateString(),
        'check_out' => now()->addDays(14)->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('reservations', 'user_id')) {
        $reservation['user_id'] = $client->id;
    }

    if (Schema::hasColumn('reservations', 'client_id')) {
        $reservation['client_id'] = $client->id;
    }

    if (Schema::hasColumn('reservations', 'paid_price')) {
        $reservation['paid_price'] = 12000;
    }

    if (Schema::hasColumn('reservations', 'price')) {
        $reservation['price'] = 12000;
    }

    DB::table('reservations')->insert($reservation);

    $this->actingAs($client)
        ->get(route('bookings.available-rooms', [
            'check_in' => $requestCheckIn,
            'check_out' => $requestCheckOut,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Bookings/available-rooms')
            ->where('filters.check_in', $requestCheckIn)
            ->where('filters.check_out', $requestCheckOut)
            ->has('rooms.data', 1)
            ->where('rooms.data.0.id', $availableRoom->id)
            ->where('rooms.data.0.number', $availableRoom->number)
            ->where('rooms.data.0.capacity', $availableRoom->capacity)
            ->where('rooms.data.0.price_in_dollars', '150.00')
            ->where('rooms.data.0.display_price', '$150.00')
            ->where('rooms.data.0.floor_name', $floor->name),
        );
});

it('paginates available rooms results', function () {
    $client = createApprovedClientForAvailableRoomsTest();

    $floor = Floor::query()->create([
        'name' => 'Pagination Floor',
        'number' => '7100',
        'created_by' => $client->id,
    ]);

    for ($index = 1; $index <= 12; $index++) {
        Room::query()->create([
            'number' => sprintf('71%02d', $index),
            'capacity' => 2,
            'price' => 10000 + ($index * 100),
            'floor_id' => $floor->id,
            'created_by' => $client->id,
        ]);
    }

    $requestCheckIn = now()->addDays(10)->toDateString();
    $requestCheckOut = now()->addDays(13)->toDateString();

    $this->actingAs($client)
        ->get(route('bookings.available-rooms', [
            'check_in' => $requestCheckIn,
            'check_out' => $requestCheckOut,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('rooms.current_page', 1)
            ->where('rooms.last_page', 2)
            ->where('rooms.total', 12)
            ->has('rooms.data', 9)
            ->has('rooms.links'),
        );

    $this->actingAs($client)
        ->get(route('bookings.available-rooms', [
            'check_in' => $requestCheckIn,
            'check_out' => $requestCheckOut,
            'page' => 2,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('rooms.current_page', 2)
            ->has('rooms.data', 3),
        );
});

it('validates the requested booking date range', function () {
    $client = createApprovedClientForAvailableRoomsTest();

    $this->actingAs($client)
        ->from(route('dashboard'))
        ->get(route('bookings.available-rooms', [
            'check_in' => now()->subDay()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
        ]))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors(['check_in']);

    $this->actingAs($client)
        ->from(route('dashboard'))
        ->get(route('bookings.available-rooms', [
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
        ]))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors(['check_in', 'check_out']);
});

function createApprovedClientForAvailableRoomsTest(): User
{
    $clientRole = Role::findOrCreate('Client');

    $client = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $client->assignRole($clientRole);

    return $client;
}
