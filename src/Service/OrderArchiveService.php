<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderArchive;
use Doctrine\ORM\EntityManagerInterface;

final class OrderArchiveService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CustomizationFileStorage $customizationFileStorage,
    ) {
    }

    public function archiveAndDelete(Order $order, ?string $archivedBy, string $reason = 'Suppression admin'): void
    {
        $archive = (new OrderArchive())
            ->setOriginalOrderId((int) $order->getId())
            ->setReference($order->getReference())
            ->setStatus($order->getStatus())
            ->setCustomerEmail($order->getCustomerEmail())
            ->setArchivedBy($archivedBy)
            ->setReason($reason)
            ->setPayload($this->buildPayload($order));

        foreach ($order->getItems() as $item) {
            $this->customizationFileStorage->remove($item->getCustomizationFilePath());
        }

        $this->entityManager->persist($archive);
        $this->entityManager->remove($order);
        $this->entityManager->flush();
    }

    private function buildPayload(Order $order): array
    {
        $items = [];
        foreach ($order->getItems() as $item) {
            $items[] = [
                'productName' => $item->getProductName(),
                'unitPrice' => $item->getUnitPrice(),
                'quantity' => $item->getQuantity(),
                'customizationText' => $item->getCustomizationText(),
                'customizationFilePath' => $item->getCustomizationFilePath(),
            ];
        }

        return [
            'id' => $order->getId(),
            'reference' => $order->getReference(),
            'status' => $order->getStatus(),
            'customerFirstName' => $order->getCustomerFirstName(),
            'customerLastName' => $order->getCustomerLastName(),
            'customerEmail' => $order->getCustomerEmail(),
            'customerPhone' => $order->getCustomerPhone(),
            'shippingAddress' => $order->getShippingAddress(),
            'additionalInfo' => $order->getAdditionalInfo(),
            'decisionReason' => $order->getDecisionReason(),
            'trackingNumber' => $order->getTrackingNumber(),
            'shippingCarrier' => $order->getShippingCarrier(),
            'total' => $order->getTotal(),
            'notes' => $order->getNotes(),
            'createdAt' => $order->getCreatedAt()->format(DATE_ATOM),
            'items' => $items,
        ];
    }
}
