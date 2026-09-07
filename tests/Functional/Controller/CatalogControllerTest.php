<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductImage;
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

            ->setIsVisible(true)
            ->setCategory($category);

        $hiddenProduct = (new Product())
            ->setName('Produit cache')
            ->setSlug('produit-cache')
            ->setPrice(1000)

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

            ->setIsVisible(false);

        $entityManager->persist($hiddenProduct);
        $entityManager->flush();

        $client->request('GET', '/catalogue/produit-cache');

        self::assertResponseStatusCodeSame(404);
    }

    public function testProductShowDisplaysGallerySliderWhenImagesArePresent(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $entityManager = $this->getEntityManager();

        $product = (new Product())
            ->setName('Coffret gravé')
            ->setSlug('coffret-grave')
            ->setPrice(2000)
            ->setIsVisible(true)
            ->setCoverImage('cover.jpg');

        $product->addImage((new ProductImage())->setFilename('gallery-1.jpg')->setSortOrder(0));
        $product->addImage((new ProductImage())->setFilename('gallery-2.jpg')->setSortOrder(1));

        $entityManager->persist($product);
        $entityManager->flush();

        $crawler = $client->request('GET', '/catalogue/coffret-grave');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-controller="product-gallery"]');
        self::assertCount(3, $crawler->filter('[data-product-gallery-target="thumbnail"]'));
    }

    public function testProductShowFallsBackToPlaceholderWithoutAnyImage(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $entityManager = $this->getEntityManager();

        $product = (new Product())
            ->setName('Sans image')
            ->setSlug('sans-image')
            ->setPrice(1500)
            ->setIsVisible(true);

        $entityManager->persist($product);
        $entityManager->flush();

        $client->request('GET', '/catalogue/sans-image');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('[data-controller="product-gallery"]');
        self::assertSelectorTextContains('body', 'Image à venir');
    }
}
