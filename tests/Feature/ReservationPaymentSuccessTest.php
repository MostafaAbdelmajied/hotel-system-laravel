<?php

use App\Enums\ReservationStatus;
use App\Enums\UserStatus;
use App\Models\Floor;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Services\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('creates reservation on payment success when webhook has not created it yet', function () {
    $client = createApprovedClientForPaymentSuccessTest();
    $room = createRoomForClientForPaymentSuccessTest($client, 31000, 4);

    $checkIn = now()->addDays(5)->toDateString();
    $checkOut = now()->addDays(7)->toDateString();

    $stripeService = Mockery::mock(StripeCheckoutService::class);
    $stripeService
        ->shouldReceive('retrieveCheckoutSession')
        ->once()
        ->with('cs_test_paid_pending')
        ->andReturn((object) [
            'id' => 'cs_test_paid_pending',
            'payment_status' => 'paid',
            'metadata' => [
                'user_id' => (string) $client->id,
                'room_id' => (string) $room->id,
                'accompany_number' => '2',
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'paid_price_snapshot_cents' => '31000',
            ],
        ]);

    $this->app->instance(StripeCheckoutService::class, $stripeService);

    $this->actingAs($client)
        ->get(route('reservations.payment.success', ['session_id' => 'cs_test_paid_pending']))
        ->assertRedirect(route('dashboard'));

    $reservation = Reservation::query()
        ->where('stripe_checkout_session_id', 'cs_test_paid_pending')
        ->first();

    expect($reservation)->not->toBeNull()
        ->and($reservation->user_id)->toBe($client->id)
        ->and($reservation->room_id)->toBe($room->id)
        ->and($reservation->accompany_number)->toBe(2)
        ->and($reservation->paid_price)->toBe(31000);
});

it('shows confirmed message on payment success when webhook already created reservation', function () {
    $client = createApprovedClientForPaymentSuccessTest();
    $room = createRoomForClientForPaymentSuccessTest($client, 29000, 3);

    Reservation::query()->create([
        'user_id' => $client->id,
        'room_id' => $room->id,
        'accompany_number' => 1,
        'check_in' => now()->addDays(2)->toDateString(),
        'check_out' => now()->addDays(4)->toDateString(),
        'paid_price' => 29000,
        'status' => ReservationStatus::CONFIRMED,
        'stripe_checkout_session_id' => 'cs_test_paid_done',
    ]);

    $stripeService = Mockery::mock(StripeCheckoutService::class);
    $stripeService
        ->shouldReceive('retrieveCheckoutSession')
        ->once()
        ->with('cs_test_paid_done')
        ->andReturn((object) [
            'id' => 'cs_test_paid_done',
            'payment_status' => 'paid',
        ]);

    $this->app->instance(StripeCheckoutService::class, $stripeService);

    $this->actingAs($client)
        ->get(route('reservations.payment.success', ['session_id' => 'cs_test_paid_done']))
        ->assertRedirect(route('dashboard'));

    expect(Reservation::query()->where('stripe_checkout_session_id', 'cs_test_paid_done')->count())->toBe(1);
});

it('forbids non-client users from confirming payment success', function () {
    $adminRole = Role::findOrCreate('Admin');
    $admin = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);
    $admin->assignRole($adminRole);

    $this->actingAs($admin)
        ->get(route('reservations.payment.success', ['session_id' => 'cs_forbidden']))
        ->assertForbidden();
});

it('does not allow client to confirm another client paid session', function () {
    $owner = createApprovedClientForPaymentSuccessTest();
    $otherClient = createApprovedClientForPaymentSuccessTest();
    $room = createRoomForClientForPaymentSuccessTest($owner, 32000, 3);

    $checkIn = now()->addDays(6)->toDateString();
    $checkOut = now()->addDays(8)->toDateString();

    $stripeService = Mockery::mock(StripeCheckoutService::class);
    $stripeService
        ->shouldReceive('retrieveCheckoutSession')
        ->once()
        ->with('cs_test_foreign_owner')
        ->andReturn((object) [
            'id' => 'cs_test_foreign_owner',
            'payment_status' => 'paid',
            'metadata' => [
                'user_id' => (string) $owner->id,
                'room_id' => (string) $room->id,
                'accompany_number' => '1',
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'paid_price_snapshot_cents' => '32000',
            ],
        ]);

    $this->app->instance(StripeCheckoutService::class, $stripeService);

    $this->actingAs($otherClient)
        ->get(route('reservations.payment.success', ['session_id' => 'cs_test_foreign_owner']))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors(['payment']);

    expect(Reservation::query()->where('stripe_checkout_session_id', 'cs_test_foreign_owner')->exists())->toBeFalse();
});

function createApprovedClientForPaymentSuccessTest(): User
{
    $clientRole = Role::findOrCreate('Client');

    $client = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);

    $client->assignRole($clientRole);

    return $client;
}

function createRoomForClientForPaymentSuccessTest(User $client, int $price, int $capacity): Room
{
    $floor = Floor::query()->create([
        'name' => fake()->words(2, true),
        'number' => (string) fake()->unique()->numberBetween(1, 99),
        'created_by' => $client->id,
    ]);

    return Room::query()->create([
        'number' => (string) fake()->unique()->numberBetween(100, 999),
        'capacity' => $capacity,
        'price' => $price,
        'floor_id' => $floor->id,
        'created_by' => $client->id,
    ]);
}
