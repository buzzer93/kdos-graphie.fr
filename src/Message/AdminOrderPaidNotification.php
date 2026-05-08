<?php

declare(strict_types=1);

namespace App\Message;

final readonly class AdminOrderPaidNotification
{
    public function __construct(
        public int $orderId,
    ) {
    }
}
