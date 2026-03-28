<?php

use App\Models\Floor;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns only available rooms for the requested date range', function () {
    $user = User::factory()->create();
    $floor = Floor::query()->create([
        'name' => 'Availability Floor',
        'number' => '5000',
        'created_by' => $user->id,
    ]);

    $reservedRoom = Room::query()->create([
        'number' => '5001',
        'capacity' => 2,
        'price' => 12000,
        'floor_id' => $floor->id,
        'created_by' => $user->id,
    ]);

    $freeRoom = Room::query()->create([
        'number' => '5002',
        'capacity' => 2,
        'price' => 13000,
        'floor_id' => $floor->id,
        'created_by' => $user->id,
    ]);

    Reservation::query()->create([
        'user_id' => $user->id,
        'room_id' => $reservedRoom->id,
        'accompany_number' => 1,
        'paid_price' => 12000,
        'check_in' => '2026-04-10',
        'check_out' => '2026-04-15',
    ]);

    $availableRoomIds = Room::query()
        ->availableBetween('2026-04-12', '2026-04-14')
        ->pluck('id');

    expect($availableRoomIds)
        ->toContain($freeRoom->id)
        ->not->toContain($reservedRoom->id);
});

it('treats boundary dates as non-overlapping when check out equals next check in', function () {
    $user = User::factory()->create();
    $floor = Floor::query()->create([
        'name' => 'Boundary Floor',
        'number' => '6000',
        'created_by' => $user->id,
    ]);

    $room = Room::query()->create([
        'number' => '6001',
        'capacity' => 2,
        'price' => 15000,
        'floor_id' => $floor->id,
        'created_by' => $user->id,
    ]);

    Reservation::query()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'accompany_number' => 0,
        'paid_price' => 15000,
        'check_in' => '2026-05-01',
        'check_out' => '2026-05-05',
    ]);

    expect($room->isAvailableBetween('2026-05-05', '2026-05-07'))->toBeTrue();
    expect($room->isAvailableBetween('2026-05-04', '2026-05-07'))->toBeFalse();
});
