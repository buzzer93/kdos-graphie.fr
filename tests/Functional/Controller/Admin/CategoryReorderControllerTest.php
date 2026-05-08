<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Tests\Functional\AbstractWebTestCase;

final class CategoryReorderControllerTest extends AbstractWebTestCase
{
    public function testReorderRequiresAuthentication(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $client->request(
            'POST',
            '/admin/categories/reorder',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X-CSRF-Token' => 'whatever'],
            json_encode(['ids' => [1, 2]], JSON_THROW_ON_ERROR)
        );

        self::assertResponseRedirects('/login');
    }

    public function testReorderUpdatesOrderOfCategories(): void
    {
        $client = $this->createAuthenticatedClient();

        $em = $this->getEntityManager();

        $cat1 = (new Category())->setName('Alpha')->setSlug('alpha')->setSortOrder(0);
        $cat2 = (new Category())->setName('Beta')->setSlug('beta')->setSortOrder(1);
        $cat3 = (new Category())->setName('Gamma')->setSlug('gamma')->setSortOrder(2);

        $em->persist($cat1);
        $em->persist($cat2);
        $em->persist($cat3);
        $em->flush();

        // GET the category index page to establish the session and extract the CSRF token
        $crawler = $client->request('GET', '/admin/categories/');
        $token = (string) $crawler
            ->filter('[data-category-reorder-csrf-token-value]')
            ->attr('data-category-reorder-csrf-token-value');

        $client->request(
            'POST',
            '/admin/categories/reorder',
            [],
            [],
            [
                'CONTENT_TYPE'      => 'application/json',
                'HTTP_X-CSRF-Token' => $token,
            ],
            json_encode(['ids' => [$cat3->getId(), $cat1->getId(), $cat2->getId()]], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertTrue($response['ok']);

        $em->clear();
        $repository = static::getContainer()->get(CategoryRepository::class);

        self::assertSame(0, $repository->find($cat3->getId())?->getSortOrder());
        self::assertSame(1, $repository->find($cat1->getId())?->getSortOrder());
        self::assertSame(2, $repository->find($cat2->getId())?->getSortOrder());
    }

    public function testReorderRejectsInvalidCsrfToken(): void
    {
        $client = $this->createAuthenticatedClient();

        $em = $this->getEntityManager();
        $cat = (new Category())->setName('Test')->setSlug('test')->setSortOrder(0);
        $em->persist($cat);
        $em->flush();

        $client->request(
            'POST',
            '/admin/categories/reorder',
            [],
            [],
            [
                'CONTENT_TYPE'      => 'application/json',
                'HTTP_X-CSRF-Token' => 'invalid-token',
            ],
            json_encode(['ids' => [$cat->getId()]], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testReorderReturnsBadRequestWhenIdsIsNotAnArray(): void
    {
        $client = $this->createAuthenticatedClient();

        $em = $this->getEntityManager();
        $cat = (new Category())->setName('Test')->setSlug('test')->setSortOrder(0);
        $em->persist($cat);
        $em->flush();

        // GET the page first to establish the session, then extract the CSRF token
        $crawler = $client->request('GET', '/admin/categories/');
        $token = (string) $crawler
            ->filter('[data-category-reorder-csrf-token-value]')
            ->attr('data-category-reorder-csrf-token-value');

        $client->request(
            'POST',
            '/admin/categories/reorder',
            [],
            [],
            [
                'CONTENT_TYPE'      => 'application/json',
                'HTTP_X-CSRF-Token' => $token,
            ],
            json_encode(['ids' => 'not-an-array'], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(400);
    }
}
