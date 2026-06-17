<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Product;
use App\Service\CartService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class CartServiceTest extends TestCase
{
    public function testAddLineUpdatesCountAndTotal(): void
    {
        $service = $this->createService();

        $product = (new Product())
            ->setName('Produit TDD')
            ->setSlug('produit-tdd')
            ->setPrice(2500)

            ->setIsVisible(true);

        $service->addLine($product, 2, 'Texte test', null);

        self::assertCount(1, $service->getLines());
        self::assertSame(2, $service->getItemsCount());
        self::assertSame(5000, $service->getTotalCents());
    }

    public function testUpdateQuantityChangesTotal(): void
    {
        $service = $this->createService();

        $product = (new Product())
            ->setName('Produit TDD')
            ->setSlug('produit-tdd')
            ->setPrice(1000)

            ->setIsVisible(true);

        $service->addLine($product, 1, null, null);
        $line = $service->getLines()[0];

        $service->updateQuantity($line['id'], 4);

        self::assertSame(4, $service->getLines()[0]['quantity']);
        self::assertSame(4000, $service->getTotalCents());
    }

    public function testRemoveLineReturnsRemovedLineAndClearsWhenAsked(): void
    {
        $service = $this->createService();

        $product = (new Product())
            ->setName('Produit TDD')
            ->setSlug('produit-tdd')
            ->setPrice(1000)

            ->setIsVisible(true);

        $service->addLine($product, 1, null, 'custom.pdf');
        $line = $service->getLines()[0];

        $removed = $service->removeLine($line['id']);

        self::assertNotNull($removed);
        self::assertSame('custom.pdf', $removed['customizationFilePath']);
        self::assertCount(0, $service->getLines());

        $service->clear();
        self::assertSame(0, $service->getItemsCount());
    }

    private function createService(): CartService
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack->push($request);

        return new CartService($requestStack);
    }
}
