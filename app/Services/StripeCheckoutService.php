<?php

namespace App\Services;

use RuntimeException;
use Stripe\StripeClient;

class StripeCheckoutService
{
    public function __construct(protected ?StripeClient $stripeClient = null) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createCheckoutSession(array $payload): object
    {
        return $this->client()->checkout->sessions->create($payload);
    }

    public function retrieveCheckoutSession(string $sessionId): object
    {
        return $this->client()->checkout->sessions->retrieve($sessionId);
    }

    protected function client(): StripeClient
    {
        if ($this->stripeClient !== null) {
            return $this->stripeClient;
        }

        $stripeSecret = (string) config('services.stripe.secret');

        if ($stripeSecret === '') {
            throw new RuntimeException('Stripe secret key is missing.');
        }

        $this->stripeClient = new StripeClient($stripeSecret);

        return $this->stripeClient;
    }
}
