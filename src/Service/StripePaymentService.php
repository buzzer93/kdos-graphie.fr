<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class StripePaymentService
{
    private readonly StripeClient $stripe;

    public function __construct(
        #[Autowire('%env(string:STRIPE_SECRET_KEY)%')]
        string $secretKey,
    ) {
        $this->stripe = new StripeClient($secretKey);
    }

    public function createPaymentIntent(Order $order): PaymentIntent
    {
        return $this->stripe->paymentIntents->create([
            'amount' => $order->getTotal(),
            'currency' => 'eur',
            'description' => 'Commande ' . $order->getReference(),
            'receipt_email' => $order->getCustomerEmail(),
            'metadata' => ['order_reference' => $order->getReference()],
        ]);
    }

    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->stripe->paymentIntents->retrieve($paymentIntentId);
    }

    /**
     * @throws SignatureVerificationException
     * @throws \UnexpectedValueException
     */
    public function constructWebhookEvent(string $payload, string $sigHeader, string $webhookSecret): Event
    {
        return \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
    }
}
