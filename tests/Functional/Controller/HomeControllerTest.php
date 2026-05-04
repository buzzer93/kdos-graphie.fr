<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\AbstractWebTestCase;

final class HomeControllerTest extends AbstractWebTestCase
{
    public function testHomePageIsAccessible(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }
}
