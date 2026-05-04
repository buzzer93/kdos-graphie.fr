<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Tests\Functional\AbstractWebTestCase;

final class CatalogControllerTest extends AbstractWebTestCase
{
    public function testCatalogShowsOnlyVisibleProducts(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $entityManager = $this->getEntityManager();

        $category = (new Category())
            ->setName('Bois')
            ->setSlug('bois')
            ->setIsVisible(true);

        $visibleProduct = (new Product())
            ->setName('Produit visible')
            ->setSlug('produit-visible')
            ->setPrice(1000)
            ->setStock(10)
            ->setIsVisible(true)
            ->setCategory($category);

        $hiddenProduct = (new Product())
            ->setName('Produit cache')
            ->setSlug('produit-cache')
            ->setPrice(1000)
            ->setStock(10)
            ->setIsVisible(false)
            ->setCategory($category);

        $entityManager->persist($category);
        $entityManager->persist($visibleProduct);
        $entityManager->persist($hiddenProduct);
        $entityManager->flush();

        $client->request('GET', '/catalogue/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Produit visible');
        self::assertSelectorTextNotContains('body', 'Produit cache');
    }

    public function testCatalogDetailReturns404ForHiddenProduct(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $entityManager = $this->getEntityManager();

        $hiddenProduct = (new Product())
            ->setName('Produit cache')
            ->setSlug('produit-cache')
            ->setPrice(1000)
            ->setStock(10)
            ->setIsVisible(false);

        $entityManager->persist($hiddenProduct);
        $entityManager->flush();

        $client->request('GET', '/catalogue/produit-cache');

        self::assertResponseStatusCodeSame(404);
    }
}
