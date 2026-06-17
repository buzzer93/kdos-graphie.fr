<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Tests\Functional\AbstractWebTestCase;

final class CategoryControllerTest extends AbstractWebTestCase
{
    public function testCategoryIndexRequiresAuthentication(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $client->request('GET', '/admin/categories/');

        self::assertResponseRedirects('/login');
    }

    public function testCategoryCrudFlow(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/categories/new');
        self::assertResponseIsSuccessful();

        $client->submitForm('Enregistrer', [
            'category[name]' => 'Papeterie',
            'category[slug]' => 'papeterie',
            'category[description]' => 'Description test',
            'category[isVisible]' => '1',
        ]);

        self::assertResponseRedirects('/admin/categories/');

        $categoryRepository = static::getContainer()->get(CategoryRepository::class);
        $category = $categoryRepository->findOneBy(['slug' => 'papeterie']);
        self::assertInstanceOf(Category::class, $category);

        $client->request('GET', '/admin/categories/' . $category->getId() . '/edit');
        $client->submitForm('Enregistrer', [
            'category[name]' => 'Papeterie MAJ',
            'category[slug]' => 'papeterie-maj',
            'category[description]' => 'Description MAJ',
            'category[isVisible]' => '1',
        ]);

        self::assertResponseRedirects('/admin/categories/');

        $updatedCategory = $categoryRepository->find($category->getId());
        self::assertSame('Papeterie MAJ', $updatedCategory?->getName());

        $crawler = $client->request('GET', '/admin/categories/');
        $deleteForm = $crawler->filter('form[action="/admin/categories/' . $category->getId() . '/delete"]')->form();
        $client->submit($deleteForm);

        self::assertResponseRedirects('/admin/categories/');
        $this->getEntityManager()->clear();
        $categoryRepository = static::getContainer()->get(CategoryRepository::class);
        self::assertNull($categoryRepository->find($category->getId()));
    }
}
