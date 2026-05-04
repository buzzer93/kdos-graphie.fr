<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Category;
use PHPUnit\Framework\TestCase;

final class CategoryTest extends TestCase
{
    public function testCategoryDefaultStateAndAccessors(): void
    {
        $category = new Category();

        self::assertTrue($category->isVisible());
        self::assertInstanceOf(\DateTimeImmutable::class, $category->getCreatedAt());

        $category
            ->setName('Papeterie')
            ->setSlug('papeterie')
            ->setDescription('Description')
            ->setIsVisible(false);

        self::assertSame('Papeterie', $category->getName());
        self::assertSame('papeterie', $category->getSlug());
        self::assertSame('Description', $category->getDescription());
        self::assertFalse($category->isVisible());
    }
}
