<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\AdminOrderPaidNotification;
use App\Repository\OrderRepository;
use App\Service\OrderMailer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class AdminOrderPaidNotificationHandler
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly OrderMailer $orderMailer,
    ) {
    }

    public function __invoke(AdminOrderPaidNotification $message): void
    {
        $order = $this->orderRepository->find($message->orderId);

        if ($order === null) {
            return;
        }

        $this->orderMailer->sendAdminOrderPaidNotification($order);
    }
}
