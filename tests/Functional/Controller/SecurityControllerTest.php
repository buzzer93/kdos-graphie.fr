<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\AbstractWebTestCase;

final class SecurityControllerTest extends AbstractWebTestCase
{
    public function testLoginPageIsAccessibleForAnonymous(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
    }

    public function testAuthenticatedUserIsRedirectedFromLogin(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/login');

        self::assertResponseRedirects('/admin/');
    }
}
