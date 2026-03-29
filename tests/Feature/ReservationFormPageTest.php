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

it('shows reservation form for a selected room with selected dates', function () {
    $client = createApprovedClient();

    $floor = Floor::query()->create([
        'name' => 'Reservation Floor',
        'number' => '9000',
        'created_by' => $client->id,
    ]);

    $room = Room::query()->create([
        'number' => '9001',
        'capacity' => 3,
        'price' => 25000,
        'floor_id' => $floor->id,
        'created_by' => $client->id,
    ]);

    $checkIn = now()->addDays(7)->toDateString();
    $checkOut = now()->addDays(10)->toDateString();

    $this->actingAs($client)
        ->get(route('reservations.rooms.show', [
            'room' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reservations/RoomReservationForm')
            ->where('room.id', $room->id)
            ->where('room.number', $room->number)
            ->where('room.capacity', 3)
            ->where('room.display_price', '$250.00')
            ->where('selected_dates.check_in', $checkIn)
            ->where('selected_dates.check_out', $checkOut)
            ->where('payment.provider', 'stripe'),
        );
});

it('rechecks availability on submit before starting payment', function () {
    $client = createApprovedClient();

    $floor = Floor::query()->create([
        'name' => 'Reservation Floor 2',
        'number' => '9100',
        'created_by' => $client->id,
    ]);

    $room = Room::query()->create([
        'number' => '9101',
        'capacity' => 2,
        'price' => 18000,
        'floor_id' => $floor->id,
        'created_by' => $client->id,
    ]);

    $checkIn = now()->addDays(8)->toDateString();
    $checkOut = now()->addDays(11)->toDateString();

    insertReservation($client->id, $room->id, now()->addDays(9)->toDateString(), now()->addDays(12)->toDateString(), 18000);

    $this->actingAs($client)
        ->from(route('reservations.rooms.show', [
            'room' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
        ]))
        ->post(route('reservations.rooms.start-payment', ['room' => $room->id]), [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'accompany_number' => 1,
        ])
        ->assertRedirect(route('reservations.rooms.show', [
            'room' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
        ]))
        ->assertSessionHasErrors(['check_in']);
});

it('does not create reservation record when payment flow starts', function () {
    $client = createApprovedClient();

    $floor = Floor::query()->create([
        'name' => 'Reservation Floor 3',
        'number' => '9200',
        'created_by' => $client->id,
    ]);

    $room = Room::query()->create([
        'number' => '9201',
        'capacity' => 4,
        'price' => 21000,
        'floor_id' => $floor->id,
        'created_by' => $client->id,
    ]);

    $checkIn = now()->addDays(12)->toDateString();
    $checkOut = now()->addDays(14)->toDateString();

    $before = DB::table('reservations')->count();

    $this->actingAs($client)
        ->post(route('reservations.rooms.start-payment', ['room' => $room->id]), [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'accompany_number' => 1,
        ])
        ->assertRedirect('https://checkout.stripe.com/c/pay/test_session');

    $after = DB::table('reservations')->count();

    expect($after)->toBe($before);
});

it('rejects past check in date when starting payment', function () {
    $client = createApprovedClient();

    $floor = Floor::query()->create([
        'name' => 'Reservation Floor 4',
        'number' => '9300',
        'created_by' => $client->id,
    ]);

    $room = Room::query()->create([
        'number' => '9301',
        'capacity' => 2,
        'price' => 22000,
        'floor_id' => $floor->id,
        'created_by' => $client->id,
    ]);

    $checkIn = now()->subDay()->toDateString();
    $checkOut = now()->addDay()->toDateString();

    $this->actingAs($client)
        ->from(route('reservations.rooms.show', [
            'room' => $room->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
        ]))
        ->post(route('reservations.rooms.start-payment', ['room' => $room->id]), [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'accompany_number' => 0,
        ])
        ->assertRedirect(route('reservations.rooms.show', [
            'room' => $room->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
        ]))
        ->assertSessionHasErrors(['check_in']);
});

function createApprovedClient(): User
{
    $clientRole = Role::findOrCreate('Client');

    $client = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $client->assignRole($clientRole);

    return $client;
}

function insertReservation(int $userId, int $roomId, string $checkIn, string $checkOut, int $price): void
{
    $reservation = [
        'room_id' => $roomId,
        'accompany_number' => 1,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('reservations', 'user_id')) {
        $reservation['user_id'] = $userId;
    }

    if (Schema::hasColumn('reservations', 'client_id')) {
        $reservation['client_id'] = $userId;
    }

    if (Schema::hasColumn('reservations', 'paid_price')) {
        $reservation['paid_price'] = $price;
    }

    if (Schema::hasColumn('reservations', 'price')) {
        $reservation['price'] = $price;
    }

    DB::table('reservations')->insert($reservation);
}
