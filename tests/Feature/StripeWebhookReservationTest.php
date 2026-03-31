<?php

use App\Enums\ReservationStatus;
use App\Enums\UserStatus;
use App\Models\Floor;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('creates a reservation when receiving a valid checkout.session.completed webhook', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test_123']);

    $client = createApprovedClientForWebhookTest();
    $room = createRoomForWebhookTest($client, 31000, 4);

    $checkIn = now()->addDays(6)->toDateString();
    $checkOut = now()->addDays(9)->toDateString();

    $payload = json_encode([
        'id' => 'evt_test_001',
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_webhook_test_001',
                'object' => 'checkout.session',
                'payment_status' => 'paid',
                'metadata' => [
                    'user_id' => (string) $client->id,
                    'room_id' => (string) $room->id,
                    'accompany_number' => '2',
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'paid_price_snapshot_cents' => '31000',
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $response = $this->withHeaders([
        'Content-Type' => 'application/json',
    ])->call('POST', route('stripe.webhook'), [], [], [], [], $payload);

    $response->assertOk()->assertJson(['received' => true]);

    $reservation = Reservation::query()
        ->where('stripe_checkout_session_id', 'cs_webhook_test_001')
        ->first();

    expect($reservation)->not->toBeNull()
        ->and($reservation->user_id)->toBe($client->id)
        ->and($reservation->room_id)->toBe($room->id)
        ->and($reservation->accompany_number)->toBe(2)
        ->and($reservation->paid_price)->toBe(31000)
        ->and($reservation->status)->toBe(ReservationStatus::CONFIRMED)
        ->and($reservation->check_in->toDateString())->toBe($checkIn)
        ->and($reservation->check_out->toDateString())->toBe($checkOut);
});

it('is idempotent when Stripe retries the same webhook event', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test_123']);

    $client = createApprovedClientForWebhookTest();
    $room = createRoomForWebhookTest($client, 27000, 3);

    $checkIn = now()->addDays(4)->toDateString();
    $checkOut = now()->addDays(7)->toDateString();

    $payload = json_encode([
        'id' => 'evt_test_002',
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_webhook_test_002',
                'object' => 'checkout.session',
                'payment_status' => 'paid',
                'metadata' => [
                    'user_id' => (string) $client->id,
                    'room_id' => (string) $room->id,
                    'accompany_number' => '1',
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'paid_price_snapshot_cents' => '27000',
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $this->withHeaders([
        'Content-Type' => 'application/json',
    ])->call('POST', route('stripe.webhook'), [], [], [], [], $payload)->assertOk();

    $this->withHeaders([
        'Content-Type' => 'application/json',
    ])->call('POST', route('stripe.webhook'), [], [], [], [], $payload)->assertOk();

    expect(Reservation::query()->where('stripe_checkout_session_id', 'cs_webhook_test_002')->count())->toBe(1);
});

it('does not create reservation from webhook when guests exceed room capacity', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test_123']);

    $client = createApprovedClientForWebhookTest();
    $room = createRoomForWebhookTest($client, 27000, 2);

    $checkIn = now()->addDays(4)->toDateString();
    $checkOut = now()->addDays(7)->toDateString();

    $payload = json_encode([
        'id' => 'evt_test_003',
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_webhook_test_003',
                'object' => 'checkout.session',
                'payment_status' => 'paid',
                'metadata' => [
                    'user_id' => (string) $client->id,
                    'room_id' => (string) $room->id,
                    'accompany_number' => '2',
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'paid_price_snapshot_cents' => '27000',
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $this->withHeaders([
        'Content-Type' => 'application/json',
    ])->call('POST', route('stripe.webhook'), [], [], [], [], $payload)->assertOk();

    expect(Reservation::query()->where('stripe_checkout_session_id', 'cs_webhook_test_003')->exists())->toBeFalse();
});

function createApprovedClientForWebhookTest(): User
{
    $clientRole = Role::findOrCreate('Client');

    $client = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);

    $client->assignRole($clientRole);

    return $client;
}

function createRoomForWebhookTest(User $client, int $price, int $capacity): Room
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
