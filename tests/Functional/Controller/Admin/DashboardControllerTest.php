<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Tests\Functional\AbstractWebTestCase;

final class DashboardControllerTest extends AbstractWebTestCase
{
    public function testDashboardRequiresAuthentication(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $client->request('GET', '/admin/');

        self::assertResponseRedirects('/login');
    }

    public function testDashboardIsAccessibleForAdmin(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/');

        self::assertResponseIsSuccessful();
    }
}
