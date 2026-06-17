<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Message\AdminOrderPaidNotification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class OrderLifecycleService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderMailer $orderMailer,
        private readonly MessageBusInterface $messageBus,
        private readonly StripePaymentService $stripePaymentService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function accept(Order $order): OrderActionResult
    {
        if ($order->getStatus() !== Order::STATUS_A_CONFIRMER) {
            return OrderActionResult::warning('Seules les commandes a confirmer peuvent etre acceptees.');
        }

        $paymentIntent = $this->stripePaymentService->createPaymentIntent($order);

        $order->setStripePaymentIntentId($paymentIntent->id);
        $order->setPaymentLink($this->urlGenerator->generate(
            'app_payment_form',
            ['reference' => $order->getReference()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        ));
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

        $this->orderMailer->sendPaymentReminder($order);

        return OrderActionResult::success('Relance de paiement envoyee.');
    }

    public function reject(Order $order, string $reason): OrderActionResult
    {
        return $this->rejectOrCancel($order, $reason, 'refusee', [
            Order::STATUS_A_CONFIRMER,
            Order::STATUS_EN_ATTENTE_PAIEMENT,
        ]);
    }

    public function cancel(Order $order, string $reason): OrderActionResult
    {
        return $this->rejectOrCancel($order, $reason, 'annulee', [
            Order::STATUS_A_CONFIRMER,
            Order::STATUS_EN_ATTENTE_PAIEMENT,
            Order::STATUS_A_FAIRE,
        ]);
    }

    public function markAsPaid(Order $order): OrderActionResult
    {
        if ($order->getStatus() !== Order::STATUS_EN_ATTENTE_PAIEMENT) {
            return OrderActionResult::warning('Seules les commandes en attente de paiement peuvent etre marquees payees.');
        }

        $order->setStatus(Order::STATUS_A_FAIRE);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new AdminOrderPaidNotification((int) $order->getId()));

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

    /** @param list<string> $allowedStatuses */
    private function rejectOrCancel(Order $order, string $reason, string $verb, array $allowedStatuses): OrderActionResult
    {
        if (!in_array($order->getStatus(), $allowedStatuses, true)) {
            return OrderActionResult::warning('Cette action n\'est pas disponible pour le statut actuel de la commande.');
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
        } else {
            $this->orderMailer->sendOrderCancelled($order);
        }

        return OrderActionResult::success('Commande ' . $verb . '.');
    }
}
