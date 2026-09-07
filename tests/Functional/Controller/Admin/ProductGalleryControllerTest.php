<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Repository\ProductRepository;
use App\Tests\Functional\AbstractWebTestCase;

final class ProductGalleryControllerTest extends AbstractWebTestCase
{
    public function testGalleryActionsRequireAuthentication(): void
    {
        $client = $this->createClientWithFreshDatabase();

        $client->request('POST', '/admin/products/1/images/new');
        self::assertResponseRedirects('/login');

        $client->request('POST', '/admin/products/1/images/1/delete');
        self::assertResponseRedirects('/login');

        $client->request('POST', '/admin/products/1/images/reorder');
        self::assertResponseRedirects('/login');
    }

    public function testUploadingAnImageAddsItToTheProductGallery(): void
    {
        $client = $this->createAuthenticatedClient();
        $entityManager = $this->getEntityManager();

        $product = (new Product())
            ->setName('Plateau bois')
            ->setSlug('plateau-bois')
            ->setPrice(2500)
            ->setIsVisible(true);

        $entityManager->persist($product);
        $entityManager->flush();
        $productId = $product->getId();

        $crawler = $client->request('GET', '/admin/products/' . $productId . '/edit');
        $form = $crawler->filter('form[action="/admin/products/' . $productId . '/images/new"]')->form();
        $form['product_image[imageFile]']->upload($this->createTestImage());

        $client->submit($form);

        self::assertResponseRedirects('/admin/products/' . $productId . '/edit');

        $entityManager->clear();
        $productRepository = static::getContainer()->get(ProductRepository::class);
        $product = $productRepository->find($productId);

        self::assertCount(1, $product?->getImages());
    }

    public function testDeletingAGalleryImageRemovesIt(): void
    {
        $client = $this->createAuthenticatedClient();
        $entityManager = $this->getEntityManager();

        $product = (new Product())
            ->setName('Cadre citation')
            ->setSlug('cadre-citation')
            ->setPrice(1800)
            ->setIsVisible(true);

        $image = (new ProductImage())->setFilename('existing.jpg')->setSortOrder(0);
        $product->addImage($image);

        $entityManager->persist($product);
        $entityManager->flush();
        $productId = $product->getId();
        $imageId = $image->getId();

        $crawler = $client->request('GET', '/admin/products/' . $productId . '/edit');
        $deleteForm = $crawler->filter('form[action="/admin/products/' . $productId . '/images/' . $imageId . '/delete"]')->form();
        $client->submit($deleteForm);

        self::assertResponseRedirects('/admin/products/' . $productId . '/edit');

        $entityManager->clear();
        $productRepository = static::getContainer()->get(ProductRepository::class);
        $product = $productRepository->find($productId);

        self::assertCount(0, $product?->getImages());
    }

    public function testReorderingGalleryImagesUpdatesSortOrder(): void
    {
        $client = $this->createAuthenticatedClient();
        $entityManager = $this->getEntityManager();

        $product = (new Product())
            ->setName('Planche apéro')
            ->setSlug('planche-apero')
            ->setPrice(3200)
            ->setIsVisible(true);

        $first = (new ProductImage())->setFilename('a.jpg')->setSortOrder(0);
        $second = (new ProductImage())->setFilename('b.jpg')->setSortOrder(1);
        $product->addImage($first);
        $product->addImage($second);

        $entityManager->persist($product);
        $entityManager->flush();
        $productId = $product->getId();
        $firstId = $first->getId();
        $secondId = $second->getId();

        // GET the edit page first to establish the session and extract the CSRF token
        $crawler = $client->request('GET', '/admin/products/' . $productId . '/edit');
        $token = (string) $crawler
            ->filter('[data-product-gallery-reorder-csrf-token-value]')
            ->attr('data-product-gallery-reorder-csrf-token-value');

        $client->request(
            'POST',
            '/admin/products/' . $productId . '/images/reorder',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X-CSRF-Token' => $token,
            ],
            json_encode(['ids' => [$secondId, $firstId]], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();

        $entityManager->clear();
        $productRepository = static::getContainer()->get(ProductRepository::class);
        $product = $productRepository->find($productId);

        $images = $product?->getImages()->toArray();
        self::assertSame($secondId, $images[0]?->getId());
        self::assertSame(0, $images[0]?->getSortOrder());
        self::assertSame($firstId, $images[1]?->getId());
        self::assertSame(1, $images[1]?->getSortOrder());
    }

    private function createTestImage(): string
    {
        // Smallest possible valid 1x1 transparent PNG, hardcoded to avoid a dependency on the GD extension.
        $onePixelPng = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true
        );

        $path = tempnam(sys_get_temp_dir(), 'gallery') . '.png';
        file_put_contents($path, $onePixelPng);

        return $path;
    }
}
