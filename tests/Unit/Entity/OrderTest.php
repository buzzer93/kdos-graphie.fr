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
            ->setStatus(Order::STATUS_PENDING)
            ->setCustomerName('John Doe')
            ->setCustomerEmail('john@example.test')
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

        self::assertSame('25,99 €', $order->getFormattedTotal());
        self::assertArrayHasKey(Order::STATUS_CONFIRMED, Order::getStatusLabels());
        self::assertArrayHasKey(Order::STATUS_SHIPPED, Order::getStatusLabels());
        self::assertArrayHasKey(Order::STATUS_DELIVERED, Order::getStatusLabels());
        self::assertArrayHasKey(Order::STATUS_CANCELLED, Order::getStatusLabels());
        self::assertInstanceOf(\DateTimeImmutable::class, $order->getCreatedAt());
    }
}
