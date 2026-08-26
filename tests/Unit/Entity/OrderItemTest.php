<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\OrderItem;
use PHPUnit\Framework\TestCase;

final class OrderItemTest extends TestCase
{
    public function testSubtotalCalculationAndAccessors(): void
    {
        $item = (new OrderItem())
            ->setProductName('Mug')
            ->setUnitPrice(1500)
            ->setQuantity(3);

        self::assertSame('Mug', $item->getProductName());
        self::assertSame(1500, $item->getUnitPrice());
        self::assertSame(3, $item->getQuantity());
        self::assertSame(4500, $item->getSubtotal());
    }
}
