<?php

declare(strict_types=1);

namespace App\Handler;

use App\Repository\OrderRepository;
use App\Service\OrderLifecycleService;
use Psr\Log\LoggerInterface;
use Stripe\Event;

final class StripeWebhookHandler
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly OrderLifecycleService $orderLifecycleService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(Event $event): void
    {
        match ($event->type) {
            'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event),
            default => null,
        };
    }

    private function handlePaymentIntentSucceeded(Event $event): void
    {
        $paymentIntentId = $event->data->object->id;

        $order = $this->orderRepository->findOneByStripePaymentIntentId($paymentIntentId);

        if ($order === null) {
            $this->logger->warning('Stripe webhook: order not found for PaymentIntent', [
                'payment_intent_id' => $paymentIntentId,
            ]);

            return;
        }

        $result = $this->orderLifecycleService->markAsPaid($order);

        if (!$result->isSuccess()) {
            $this->logger->warning('Stripe webhook: markAsPaid returned non-success', [
                'order_reference' => $order->getReference(),
                'message' => $result->getMessage(),
            ]);
        }
    }
}
