<?php

declare(strict_types=1);

namespace App\Tests\Stub;

use App\Entity\Order;
use App\Service\StripePaymentService;
use Stripe\Event;
use Stripe\PaymentIntent;

final class StripePaymentServiceStub extends StripePaymentService
{
    public function __construct()
    {
        // No-arg constructor: bypasses the real __construct that needs STRIPE_SECRET_KEY.
    }

    public function createPaymentIntent(Order $order): PaymentIntent
    {
        return PaymentIntent::constructFrom(['id' => 'pi_stub_' . substr(md5($order->getReference()), 0, 8)]);
    }

    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::constructFrom([
            'id'       => $paymentIntentId,
            'status'   => 'succeeded',
            'amount'   => 1000,
            'currency' => 'eur',
        ]);
    }

    public function constructWebhookEvent(string $payload, string $sigHeader, string $webhookSecret): Event
    {
        throw new \LogicException('constructWebhookEvent is not available in tests.');
    }
}
