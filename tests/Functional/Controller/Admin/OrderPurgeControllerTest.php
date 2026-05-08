<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\Order;
use App\Entity\OrderArchive;
use App\Repository\OrderRepository;
use App\Tests\Functional\AbstractWebTestCase;

final class OrderPurgeControllerTest extends AbstractWebTestCase
{
    public function testPurgeRequiresAuthentication(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $client->request('POST', '/admin/orders/purge');

        self::assertResponseRedirects('/login');
    }

    public function testPurgeWithNoEligibleOrdersFlashesWarning(): void
    {
        $client = $this->createAuthenticatedClient();

        $crawler = $client->request('GET', '/admin/orders/');
        $token = (string) $crawler->filter('form[action="/admin/orders/purge"] input[name="_token"]')->attr('value');

        $client->request('POST', '/admin/orders/purge', ['_token' => $token]);

        self::assertResponseRedirects('/admin/orders/');
        $client->followRedirect();
        self::assertStringContainsString('éligible', (string) $client->getResponse()->getContent());
    }

    public function testPurgeArchivesAndDeletesOldTerminalOrders(): void
    {
        $client = $this->createAuthenticatedClient();

        $em = $this->getEntityManager();
        $connection = $em->getConnection();

        $statuses = [Order::STATUS_TERMINE, Order::STATUS_REFUSE, Order::STATUS_ANNULE];
        $orderIds = [];

        foreach ($statuses as $i => $status) {
            $order = (new Order())
                ->setReference('ORD-PURGE-' . $i)
                ->setStatus($status)
                ->setCustomerFirstName('Test')
                ->setCustomerLastName('User')
                ->setCustomerEmail('test@example.test')
                ->setCustomerPhone('0600000000')
                ->setShippingAddress('1 rue du Test')
                ->setTotal(1000);
            $em->persist($order);
            $em->flush();
            $orderIds[] = $order->getId();
        }

        $oldDate = (new \DateTimeImmutable('-31 days'))->format('Y-m-d H:i:s');
        foreach ($orderIds as $id) {
            $connection->executeStatement(
                'UPDATE `order` SET created_at = :date WHERE id = :id',
                ['date' => $oldDate, 'id' => $id]
            );
        }

        $crawler = $client->request('GET', '/admin/orders/');
        $token = (string) $crawler->filter('form[action="/admin/orders/purge"] input[name="_token"]')->attr('value');

        $client->request('POST', '/admin/orders/purge', ['_token' => $token]);

        self::assertResponseRedirects('/admin/orders/');
        $client->followRedirect();
        self::assertStringContainsString('archivée', (string) $client->getResponse()->getContent());

        $em->clear();
        $orderRepository = static::getContainer()->get(OrderRepository::class);

        foreach ($orderIds as $id) {
            self::assertNull($orderRepository->find($id));
        }

        self::assertSame(3, $em->getRepository(OrderArchive::class)->count([]));
    }

    public function testPurgeDoesNotDeleteRecentOrders(): void
    {
        $client = $this->createAuthenticatedClient();

        $em = $this->getEntityManager();
        $order = (new Order())
            ->setReference('ORD-PURGE-RECENT')
            ->setStatus(Order::STATUS_TERMINE)
            ->setCustomerFirstName('Test')
            ->setCustomerLastName('User')
            ->setCustomerEmail('test@example.test')
            ->setCustomerPhone('0600000000')
            ->setShippingAddress('1 rue du Test')
            ->setTotal(1000);
        $em->persist($order);
        $em->flush();

        $crawler = $client->request('GET', '/admin/orders/');
        $token = (string) $crawler->filter('form[action="/admin/orders/purge"] input[name="_token"]')->attr('value');

        $client->request('POST', '/admin/orders/purge', ['_token' => $token]);

        self::assertResponseRedirects('/admin/orders/');

        $em->clear();
        $orderRepository = static::getContainer()->get(OrderRepository::class);
        self::assertNotNull($orderRepository->find($order->getId()));
    }

    public function testPurgeDoesNotDeleteActiveOrders(): void
    {
        $client = $this->createAuthenticatedClient();

        $em = $this->getEntityManager();
        $connection = $em->getConnection();

        $order = (new Order())
            ->setReference('ORD-PURGE-ACTIVE')
            ->setStatus(Order::STATUS_A_FAIRE)
            ->setCustomerFirstName('Test')
            ->setCustomerLastName('User')
            ->setCustomerEmail('test@example.test')
            ->setCustomerPhone('0600000000')
            ->setShippingAddress('1 rue du Test')
            ->setTotal(1000);
        $em->persist($order);
        $em->flush();

        $oldDate = (new \DateTimeImmutable('-31 days'))->format('Y-m-d H:i:s');
        $connection->executeStatement(
            'UPDATE `order` SET created_at = :date WHERE id = :id',
            ['date' => $oldDate, 'id' => $order->getId()]
        );

        $crawler = $client->request('GET', '/admin/orders/');
        $token = (string) $crawler->filter('form[action="/admin/orders/purge"] input[name="_token"]')->attr('value');

        $client->request('POST', '/admin/orders/purge', ['_token' => $token]);

        $em->clear();
        $orderRepository = static::getContainer()->get(OrderRepository::class);
        self::assertNotNull($orderRepository->find($order->getId()));
    }

    public function testPurgeRejectsInvalidCsrfToken(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/admin/orders/purge', ['_token' => 'invalid-token']);

        self::assertResponseRedirects('/admin/orders/');
        $client->followRedirect();
        self::assertStringContainsString('token CSRF', (string) $client->getResponse()->getContent());
    }
}
