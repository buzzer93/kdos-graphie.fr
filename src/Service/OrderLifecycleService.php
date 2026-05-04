<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Service\OrderActionResult;
use Doctrine\ORM\EntityManagerInterface;

final class OrderLifecycleService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderMailer $orderMailer,
    ) {
    }

    public function accept(Order $order): OrderActionResult
    {
        if ($order->getStatus() !== Order::STATUS_A_CONFIRMER) {
            return OrderActionResult::warning('Seules les commandes a confirmer peuvent etre acceptees.');
        }

        $order->setStatus(Order::STATUS_EN_ATTENTE_PAIEMENT);
        $this->entityManager->flush();
        $this->orderMailer->sendOrderConfirmedForPayment($order);

        return OrderActionResult::success('Commande acceptee. Statut passe a En attente de paiement.');
    }

    public function remindPayment(Order $order): OrderActionResult
    {
        if ($order->getStatus() !== Order::STATUS_EN_ATTENTE_PAIEMENT) {
            return OrderActionResult::warning('La relance est disponible uniquement pour les commandes en attente de paiement.');
        }

        return OrderActionResult::success('Relance de paiement enregistree.');
    }

    public function reject(Order $order, string $reason): OrderActionResult
    {
        return $this->rejectOrCancel($order, $reason, 'refusee');
    }

    public function cancel(Order $order, string $reason): OrderActionResult
    {
        return $this->rejectOrCancel($order, $reason, 'annulee');
    }

    public function markAsPaid(Order $order): OrderActionResult
    {
        if ($order->getStatus() !== Order::STATUS_EN_ATTENTE_PAIEMENT) {
            return OrderActionResult::warning('Seules les commandes en attente de paiement peuvent etre marquees payees.');
        }

        $order->setStatus(Order::STATUS_A_FAIRE);
        $this->entityManager->flush();

        return OrderActionResult::success('Paiement confirme. Commande passee au statut A faire.');
    }

    public function complete(Order $order): OrderActionResult
    {
        if ($order->getStatus() !== Order::STATUS_A_FAIRE) {
            return OrderActionResult::warning('Seules les commandes a faire peuvent etre terminees.');
        }

        $order->setStatus(Order::STATUS_TERMINE);
        $this->entityManager->flush();
        $this->orderMailer->sendOrderDoneShipping($order);

        return OrderActionResult::success('Commande terminee.');
    }

    private function rejectOrCancel(Order $order, string $reason, string $verb): OrderActionResult
    {
        if (!in_array($order->getStatus(), [Order::STATUS_A_CONFIRMER, Order::STATUS_EN_ATTENTE_PAIEMENT], true)) {
            return OrderActionResult::warning('Action disponible uniquement pour les commandes a confirmer ou en attente de paiement.');
        }

        $normalizedReason = trim($reason);

        if ($normalizedReason === '') {
            return OrderActionResult::danger('Le motif est obligatoire.');
        }

        $prefix = ucfirst($verb) . ': ' . $normalizedReason;
        $existingNotes = trim((string) $order->getNotes());

        $order->setDecisionReason($normalizedReason);
        $order->setNotes($existingNotes === '' ? $prefix : $existingNotes . "\n\n" . $prefix);
        $order->setStatus($verb === 'refusee' ? Order::STATUS_REFUSE : Order::STATUS_ANNULE);

        $this->entityManager->flush();

        if ($verb === 'refusee') {
            $this->orderMailer->sendOrderRefused($order);
        }

        return OrderActionResult::success('Commande ' . $verb . '.');
    }
}
