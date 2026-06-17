<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Tests\Functional\AbstractWebTestCase;

final class ProductControllerTest extends AbstractWebTestCase
{
    public function testProductIndexRequiresAuthentication(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $client->request('GET', '/admin/products/');

        self::assertResponseRedirects('/login');
    }

    public function testProductCrudFlow(): void
    {
        $client = $this->createAuthenticatedClient();
        $entityManager = $this->getEntityManager();

        $category = (new Category())
            ->setName('Cadeaux')
            ->setSlug('cadeaux')
            ->setIsVisible(true);

        $entityManager->persist($category);
        $entityManager->flush();

        $client->request('GET', '/admin/products/new');
        $client->submitForm('Enregistrer', [
            'product[name]' => 'Mug Perso',
            'product[slug]' => 'mug-perso',
            'product[description]' => 'Desc test',
            'product[price]' => '1590',
            'product[category]' => (string) $category->getId(),
            'product[isVisible]' => '1',
        ]);

        self::assertResponseRedirects('/admin/products/');

        $productRepository = static::getContainer()->get(ProductRepository::class);
        $product = $productRepository->findOneBy(['slug' => 'mug-perso']);
        self::assertInstanceOf(Product::class, $product);

        $client->request('GET', '/admin/products/' . $product->getId() . '/edit');
        $client->submitForm('Enregistrer', [
            'product[name]' => 'Mug Perso MAJ',
            'product[slug]' => 'mug-perso-maj',
            'product[description]' => 'Desc maj',
            'product[price]' => '1990',
            'product[category]' => (string) $category->getId(),
            'product[isVisible]' => '1',
        ]);

        self::assertResponseRedirects('/admin/products/');

        $updatedProduct = $productRepository->find($product->getId());
        self::assertSame('Mug Perso MAJ', $updatedProduct?->getName());
        self::assertSame(1990, $updatedProduct?->getPrice());

        $crawler = $client->request('GET', '/admin/products/');
        $deleteForm = $crawler->filter('form[action="/admin/products/' . $product->getId() . '/delete"]')->form();
        $client->submit($deleteForm);

        self::assertResponseRedirects('/admin/products/');
        $this->getEntityManager()->clear();
        $productRepository = static::getContainer()->get(ProductRepository::class);
        self::assertNull($productRepository->find($product->getId()));
    }
}
