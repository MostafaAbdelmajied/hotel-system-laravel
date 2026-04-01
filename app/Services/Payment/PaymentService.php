<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\Stripe\StripeService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Stripe\Webhook;

class PaymentService
{
    public function __construct(protected readonly StripeService $stripeService) {}

    public function createCheckout(Reservation $reservation)
    {
        return DB::transaction(function () use ($reservation) {

            $reservation->loadMissing('room');
            $user = request()->user();

            $payment = $reservation->payments()->create([
                'amount' => $reservation->total_price,
                'currency' => 'usd',
                'status' => PaymentStatus::PENDING,
            ]);

            $successUrl = route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = route('payment.cancel', ['payment' => $payment->id]);

            $data = [
                'mode' => 'payment',
                'client_reference_id' => (string) $user?->id,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'expires_at' => now()->addMinutes(30)->timestamp,
                'line_items' => [
                    [
                        'quantity' => 1,
                        'price_data' => [
                            'currency' => 'usd',
                            'unit_amount' => $payment->amount,
                            'product_data' => [
                                'name' => "Room {$reservation->room->number} reservation",
                            ],
                        ],
                    ],
                ],
            ];

            $checkoutSession = $this->stripeService->createCheckoutSession($data);

            $payment->update([
                'stripe_session_id' => $checkoutSession->id,
            ]);

            return $checkoutSession;
        });
    }

    public function handleSuccess($request)
    {
        DB::beginTransaction();
        try {
            $sessionId = $request->query('session_id');
            if (!$sessionId) {
                abort(400, 'Missing session_id');
            }

            $session = $this->stripeService->retrieveSession($sessionId);
            if (!$session || $session->payment_status !== 'paid') {
                abort(400, 'Invalid session or payment not completed');
            }

            $payment = Payment::where('stripe_session_id', $sessionId)->firstOrFail();
            $payment->update([
                'status' => PaymentStatus::PAID,
                'paid_at' => now(),
            ]);
            $payment->reservation()->update([
                'status' => ReservationStatus::CONFIRMED,
            ]);

            return to_route('dashboard')->with('success', 'Payment completed and reservation confirmed.');

        }catch (RuntimeException|ApiErrorException) {
            return to_route('dashboard')->with('error', 'Unable to verify Stripe payment at the moment.');
        }
    }

    public function handleCancel($payment)
    {
        $payment->update([
            'status' => PaymentStatus::FAILED,
        ]);
        $payment->reservation()->update([
            'status' => ReservationStatus::CANCELLED,
        ]);

        return to_route('dashboard')->with('error', 'Reservation cancelled');
    }

    public function handleWebhook($request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Exception $e) {
            return response('Invalid signature', 400);
        }

        match ($event->type) {
            'checkout.session.completed' =>
                $this->handleWebhookSuccess($event->data->object),

            default => null,
        };

        return response('OK', 200);
    }

    public function handleWebhookSuccess($session)
    {
        DB::transaction(function () use ($session) {

            $payment = Payment::where('stripe_session_id', $session->id)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                return;
            }

            $reservation = $payment->reservation;

            if ($payment->status === PaymentStatus::PAID) {
                return;
            }

            $payment->update([
                'status' => PaymentStatus::PAID,
                'paid_at' => now(),
                'stripe_payment_intent_id' => $session->payment_intent,
            ]);

            $reservation->update([
                'status' => ReservationStatus::CONFIRMED,
            ]);
        });
    }
}
