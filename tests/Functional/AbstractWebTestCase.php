<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

abstract class AbstractWebTestCase extends WebTestCase
{
    protected function createClientWithFreshDatabase(): KernelBrowser
    {
        $client = static::createClient();
        $this->recreateSchema();

        return $client;
    }

    protected function createAuthenticatedClient(): KernelBrowser
    {
        $client = $this->createClientWithFreshDatabase();

        $user = (new User())
            ->setEmail('admin@test.local')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword('hashed-password');

        $entityManager = $this->getEntityManager();
        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);

        return $client;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function csrfToken(string $tokenId): string
    {
        $requestStack = static::getContainer()->get(RequestStack::class);

        if ($requestStack->getCurrentRequest() === null) {
            $request = new Request();
            $request->setSession(new Session(new MockArraySessionStorage()));
            $requestStack->push($request);
        }

        $tokenManager = static::getContainer()->get(CsrfTokenManagerInterface::class);

        return $tokenManager->getToken($tokenId)->getValue();
    }

    private function recreateSchema(): void
    {
        $entityManager = $this->getEntityManager();
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        if ($metadata === []) {
            return;
        }

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $entityManager->clear();
    }
}
