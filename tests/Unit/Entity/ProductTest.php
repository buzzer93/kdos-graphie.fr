<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Category;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function testProductAccessorsAndTouchUpdateTimestamp(): void
    {
        $category = (new Category())
            ->setName('Bois')
            ->setSlug('bois');

        $product = new Product();
        $initialUpdatedAt = $product->getUpdatedAt();

        usleep(1000);
        $product->touch();

        $product
            ->setName('Planche gravee')
            ->setSlug('planche-gravee')
            ->setDescription('Desc')
            ->setPrice(1999)
            ->setIsVisible(false)
            ->setCoverImage('cover.jpg')
            ->setCategory($category);

        self::assertSame('Planche gravee', $product->getName());
        self::assertSame('planche-gravee', $product->getSlug());
        self::assertSame('Desc', $product->getDescription());
        self::assertSame(1999, $product->getPrice());
        self::assertFalse($product->isVisible());
        self::assertSame('cover.jpg', $product->getCoverImage());
        self::assertSame($category, $product->getCategory());
        self::assertGreaterThan($initialUpdatedAt, $product->getUpdatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $product->getCreatedAt());
    }
}
