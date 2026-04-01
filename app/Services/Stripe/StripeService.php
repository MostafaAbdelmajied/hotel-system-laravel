<?php

namespace App\Services\Stripe;

use Stripe\StripeClient;

class StripeService
{
    protected StripeClient $client;

    public function __construct()
    {
        $this->client = new StripeClient(config('services.stripe.secret'));
    }

    public function createCheckoutSession(array $payload): object
    {
        return $this->client->checkout->sessions->create($payload);
    }

        public function retrieveSession(string $sessionId): object
        {
            return $this->client->checkout->sessions->retrieve($sessionId);
        }

    public function refund(string $paymentIntentId)
    {
        return $this->client->refunds->create([
            'payment_intent' => $paymentIntentId,
        ]);
    }
}
