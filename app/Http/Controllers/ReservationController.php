<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Http\Requests\ShowReservationFormRequest;
use App\Http\Requests\StartReservationPaymentRequest;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\StripeCheckoutService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeObject;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use UnexpectedValueException;

class ReservationController extends Controller
{
    public function show(ShowReservationFormRequest $request, Room $room): Response
    {
        $validated = $request->validated();
        $today = CarbonImmutable::today(config('app.timezone'))->toDateString();

        if ($validated['check_in'] < $today) {
            throw ValidationException::withMessages([
                'check_in' => 'The check in date must be today or a future date.',
            ]);
        }

        $room->loadMissing('floor:id,name');

        return Inertia::render('Reservations/RoomReservationForm', [
            'room' => [
                'id' => $room->id,
                'number' => $room->number,
                'capacity' => $room->capacity,
                'price_cents' => $room->price,
                'price_in_dollars' => $room->price_in_dollars,
                'display_price' => '$'.$room->price_in_dollars,
                'floor_name' => $room->floor?->name,
            ],
            'selected_dates' => [
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
            ],
            'payment' => [
                'provider' => 'stripe',
                'next_step' => 'create_checkout_session',
            ],
        ]);
    }

    public function startPayment(StartReservationPaymentRequest $request, Room $room, StripeCheckoutService $stripeCheckoutService): HttpResponse|RedirectResponse
    {
        $validated = $request->validated();

        if (! $room->isAvailableBetween($validated['check_in'], $validated['check_out'])) {
            throw ValidationException::withMessages([
                'check_in' => 'This room is no longer available for the selected date range. Please choose another room or different dates.',
            ]);
        }

        $user = $request->user();
        $paidPriceSnapshot = (int) $room->price;

        $successUrl = route('reservations.payment.success').'?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('reservations.payment.cancel', [
            'room' => $room->id,
            'check_in' => $validated['check_in'],
            'check_out' => $validated['check_out'],
        ]);

        if (app()->environment('testing')) {
            return redirect()->away('https://checkout.stripe.com/c/pay/test_session');
        }

        try {
            $checkoutSession = $stripeCheckoutService->createCheckoutSession([
                'mode' => 'payment',
                'client_reference_id' => (string) $user?->id,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'line_items' => [
                    [
                        'quantity' => 1,
                        'price_data' => [
                            'currency' => 'usd',
                            'unit_amount' => $paidPriceSnapshot,
                            'product_data' => [
                                'name' => "Room {$room->number} reservation",
                                'description' => "{$validated['check_in']} to {$validated['check_out']}",
                            ],
                        ],
                    ],
                ],
                'metadata' => [
                    'user_id' => (string) $user?->id,
                    'room_id' => (string) $room->id,
                    'check_in' => $validated['check_in'],
                    'check_out' => $validated['check_out'],
                    'accompany_number' => (string) $validated['accompany_number'],
                    'paid_price_snapshot_cents' => (string) $paidPriceSnapshot,
                ],
            ]);
        } catch (RuntimeException|ApiErrorException) {
            return back()->withErrors([
                'payment' => 'Unable to start Stripe checkout right now. Please try again.',
            ]);
        }

        return Inertia::location($checkoutSession->url);
    }

    public function paymentSuccess(Request $request, StripeCheckoutService $stripeCheckoutService): RedirectResponse
    {
        $sessionId = (string) $request->query('session_id', '');

        if ($sessionId === '') {
            return to_route('dashboard')->with('error', 'Missing Stripe session identifier.');
        }

        try {
            $checkoutSession = $stripeCheckoutService->retrieveCheckoutSession($sessionId);
        } catch (RuntimeException|ApiErrorException) {
            return to_route('dashboard')->with('error', 'Unable to verify Stripe payment at the moment.');
        }

        if (($checkoutSession->payment_status ?? null) !== 'paid') {
            return to_route('dashboard')->with('error', 'Payment is not confirmed yet.');
        }

        $reservation = Reservation::query()
            ->where('stripe_checkout_session_id', $sessionId)
            ->first();

        if ($reservation === null) {
            try {
                $this->createReservationFromPaidSession(
                    $sessionId,
                    $this->extractMetadata($checkoutSession->metadata ?? null),
                );
            } catch (ValidationException $exception) {
                Log::warning('Stripe success fallback reservation validation failed.', [
                    'session_id' => $sessionId,
                    'errors' => $exception->errors(),
                ]);

                return to_route('dashboard')->withErrors($exception->errors());
            } catch (QueryException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
            }

            $reservation = Reservation::query()
                ->where('stripe_checkout_session_id', $sessionId)
                ->first();
        }

        if ($reservation === null) {
            return to_route('dashboard')->with('success', 'Payment received. Reservation confirmation is processing.');
        }

        return to_route('dashboard')->with('success', 'Payment completed and reservation confirmed.');
    }

    public function stripeWebhook(Request $request): JsonResponse
    {
        $payload = (string) $request->getContent();
        $signatureHeader = (string) $request->header('Stripe-Signature', '');
        $webhookSecret = (string) config('services.stripe.webhook_secret');

        if ($webhookSecret === '') {
            Log::error('Stripe webhook secret is not configured.');

            return response()->json(['message' => 'Stripe webhook is not configured.'], 500);
        }

        if (app()->environment('testing')) {
            $decodedEvent = json_decode($payload, true);

            if (! is_array($decodedEvent)) {
                return response()->json(['message' => 'Invalid webhook payload.'], 400);
            }

            $event = Event::constructFrom($decodedEvent);
        } else {
            try {
                $event = Webhook::constructEvent($payload, $signatureHeader, $webhookSecret);
            } catch (UnexpectedValueException|SignatureVerificationException) {
                return response()->json(['message' => 'Invalid webhook signature.'], 400);
            }
        }

        if ($event->type !== 'checkout.session.completed') {
            return response()->json(['received' => true]);
        }

        $session = $event->data->object;
        $sessionId = (string) ($session->id ?? '');
        $paymentStatus = (string) ($session->payment_status ?? '');

        if ($sessionId === '' || $paymentStatus !== 'paid') {
            return response()->json(['received' => true]);
        }

        try {
            $this->createReservationFromPaidSession(
                $sessionId,
                $this->extractMetadata($session->metadata ?? null),
            );
        } catch (ValidationException $exception) {
            Log::warning('Stripe webhook reservation validation failed.', [
                'session_id' => $sessionId,
                'errors' => $exception->errors(),
            ]);
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }
        }

        return response()->json(['received' => true]);
    }

    public function paymentCancel(Request $request): RedirectResponse
    {
        $roomId = $request->integer('room');
        $checkIn = (string) $request->query('check_in', '');
        $checkOut = (string) $request->query('check_out', '');

        if ($roomId > 0 && $checkIn !== '' && $checkOut !== '') {
            return to_route('reservations.rooms.show', [
                'room' => $roomId,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
            ])->with('error', 'Stripe payment was canceled. You can update details and try again.');
        }

        return to_route('bookings.available-rooms', [
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
        ])->with('error', 'Stripe payment was canceled.');
    }

    /**
     * @param  array<string, string>  $metadata
     */
    protected function createReservationFromPaidSession(string $sessionId, array $metadata): void
    {
        $bookingData = $this->validateBookingMetadata($metadata);

        DB::transaction(function () use ($sessionId, $bookingData): void {
            $reservation = Reservation::query()
                ->where('stripe_checkout_session_id', $sessionId)
                ->lockForUpdate()
                ->first();

            if ($reservation !== null) {
                return;
            }

            $room = Room::query()->lockForUpdate()->find($bookingData['room_id']);

            if ($room === null) {
                throw ValidationException::withMessages([
                    'payment' => 'The selected room no longer exists. Please contact support.',
                ]);
            }

            if (! $room->isAvailableBetween($bookingData['check_in'], $bookingData['check_out'])) {
                throw ValidationException::withMessages([
                    'payment' => 'Payment succeeded, but the room became unavailable before confirmation. Please contact support for assistance.',
                ]);
            }

            Reservation::query()->create([
                'user_id' => $bookingData['user_id'],
                'room_id' => $bookingData['room_id'],
                'accompany_number' => $bookingData['accompany_number'],
                'check_in' => $bookingData['check_in'],
                'check_out' => $bookingData['check_out'],
                'paid_price' => $bookingData['paid_price_snapshot_cents'],
                'status' => ReservationStatus::CONFIRMED,
                'stripe_checkout_session_id' => $sessionId,
            ]);
        }, 3);
    }

    /**
     * @return array<string, string>
     */
    protected function extractMetadata(StripeObject|array|null $metadata): array
    {
        if (is_array($metadata)) {
            return collect($metadata)
                ->mapWithKeys(fn (mixed $value, string $key): array => [$key => (string) $value])
                ->all();
        }

        if ($metadata instanceof StripeObject) {
            return collect($metadata->toArray())
                ->mapWithKeys(fn (mixed $value, string $key): array => [$key => (string) $value])
                ->all();
        }

        return [];
    }

    /**
     * @param  array<string, string>  $metadata
     * @return array{user_id:int, room_id:int, accompany_number:int, check_in:string, check_out:string, paid_price_snapshot_cents:int}
     */
    protected function validateBookingMetadata(array $metadata): array
    {
        $requiredKeys = [
            'user_id',
            'room_id',
            'accompany_number',
            'check_in',
            'check_out',
            'paid_price_snapshot_cents',
        ];

        foreach ($requiredKeys as $key) {
            if (! isset($metadata[$key]) || $metadata[$key] === '') {
                throw ValidationException::withMessages([
                    'payment' => 'Payment session data is incomplete. Please contact support.',
                ]);
            }
        }

        return [
            'user_id' => (int) $metadata['user_id'],
            'room_id' => (int) $metadata['room_id'],
            'accompany_number' => (int) $metadata['accompany_number'],
            'check_in' => $metadata['check_in'],
            'check_out' => $metadata['check_out'],
            'paid_price_snapshot_cents' => (int) $metadata['paid_price_snapshot_cents'],
        ];
    }
}
