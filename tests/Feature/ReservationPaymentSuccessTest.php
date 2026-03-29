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

it('shows processing message on payment success when reservation is not yet created by webhook', function () {
    $client = createApprovedClientForPaymentSuccessTest();

    $stripeService = Mockery::mock(StripeCheckoutService::class);
    $stripeService
        ->shouldReceive('retrieveCheckoutSession')
        ->once()
        ->with('cs_test_paid_pending')
        ->andReturn((object) [
            'id' => 'cs_test_paid_pending',
            'payment_status' => 'paid',
        ]);

    $this->app->instance(StripeCheckoutService::class, $stripeService);

    $this->actingAs($client)
        ->get(route('reservations.payment.success', ['session_id' => 'cs_test_paid_pending']))
        ->assertRedirect(route('dashboard'));

    expect(Reservation::query()->count())->toBe(0);
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
