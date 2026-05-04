<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Order;
use App\Entity\OrderItem;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testOrderLabelsFormattingAndItemsAssociation(): void
    {
        $order = (new Order())
            ->setReference('ORD-20260504-AAAAAA')
            ->setStatus(Order::STATUS_A_CONFIRMER)
            ->setCustomerFirstName('John')
            ->setCustomerLastName('Doe')
            ->setCustomerEmail('john@example.test')
            ->setCustomerPhone('0600000001')
            ->setShippingAddress("10 rue du Test\n75000 Paris")
            ->setTotal(2599)
            ->setNotes('Note test');

        $item = (new OrderItem())
            ->setProductName('Carte')
            ->setUnitPrice(1299)
            ->setQuantity(2);

        $order->addItem($item);

        self::assertCount(1, $order->getItems());
        self::assertSame($order, $item->getOrder());

        $order->removeItem($item);
        self::assertCount(0, $order->getItems());
        self::assertNull($item->getOrder());

        self::assertSame('John Doe', $order->getCustomerFullName());
        self::assertSame('25,99 €', $order->getFormattedTotal());
        self::assertArrayHasKey(Order::STATUS_EN_ATTENTE_PAIEMENT, Order::getStatusLabels());
        self::assertArrayHasKey(Order::STATUS_A_FAIRE, Order::getStatusLabels());
        self::assertArrayHasKey(Order::STATUS_TERMINE, Order::getStatusLabels());
        self::assertArrayHasKey(Order::STATUS_REFUSE, Order::getStatusLabels());
        self::assertArrayHasKey(Order::STATUS_ANNULE, Order::getStatusLabels());
        self::assertInstanceOf(\DateTimeImmutable::class, $order->getCreatedAt());
    }
}
