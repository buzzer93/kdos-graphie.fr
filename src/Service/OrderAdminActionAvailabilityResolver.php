<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;

final class OrderAdminActionAvailabilityResolver
{
    public function resolve(Order $order): OrderAdminActions
    {
        $status = $order->getStatus();

        return new OrderAdminActions(
            canAccept: $status === Order::STATUS_A_CONFIRMER,
            canReject: in_array($status, [Order::STATUS_A_CONFIRMER, Order::STATUS_EN_ATTENTE_PAIEMENT], true),
            canCancel: in_array($status, [Order::STATUS_A_CONFIRMER, Order::STATUS_EN_ATTENTE_PAIEMENT, Order::STATUS_A_FAIRE], true),
            canComplete: $status === Order::STATUS_A_FAIRE,
            canMarkPaid: $status === Order::STATUS_EN_ATTENTE_PAIEMENT,
            canRemindPayment: $status === Order::STATUS_EN_ATTENTE_PAIEMENT,
            canRequestInfo: in_array($status, [Order::STATUS_A_CONFIRMER, Order::STATUS_EN_ATTENTE_PAIEMENT, Order::STATUS_A_FAIRE], true),
        );
    }
}
