<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Http\Requests\ShowReservationFormRequest;
use App\Http\Requests\StartReservationPaymentRequest;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\Payment\PaymentService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReservationController extends Controller
{
    public function show(ShowReservationFormRequest $request, Room $room): Response
    {
        $this->authorize('create', Reservation::class);

        $validated = $request->validated();
        $today = CarbonImmutable::today(config('app.timezone'))->toDateString();
        $stripeConfigured = (string) config('services.stripe.key') !== ''
            && (string) config('services.stripe.secret') !== '';

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
                'is_configured' => $stripeConfigured,
                'configuration_message' => $stripeConfigured
                    ? null
                    : 'Stripe is not configured yet. Please contact support.',
            ],
        ]);
    }

    public function startPayment(StartReservationPaymentRequest $request, Room $room, PaymentService $paymentService): HttpResponse|RedirectResponse
    {
        $this->authorize('create', Reservation::class);

        $validated = $request->validated();

        if (! $room->isAvailableBetween($validated['check_in'], $validated['check_out'])) {
            throw ValidationException::withMessages([
                'check_in' => 'This room is no longer available for the selected date range. Please choose another room or different dates.',
            ]);
        }

        $numberOfNights = Carbon::parse($validated['check_in'])->diffInDays(Carbon::parse($validated['check_out']), true);
        $total_price = $room->price * (int) $numberOfNights;
        $reservation = Reservation::create([
            'user_id' => $request->user()->id,
            'room_id' => $room->id,
            'accompany_number' => $validated['accompany_number'],
            'check_in' => $validated['check_in'],
            'check_out' => $validated['check_out'],
            'total_price' => $total_price,
            'status' => ReservationStatus::PENDING,
        ]);

        $checkoutSession = $paymentService->createCheckout($reservation);

        return Inertia::location($checkoutSession->url);
    }


}
